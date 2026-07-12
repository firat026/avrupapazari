from dotenv import load_dotenv
from pathlib import Path
import os

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

from fastapi import FastAPI, APIRouter, HTTPException, Request, Response, Depends
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
from pydantic import BaseModel, Field, BeforeValidator, ConfigDict
from typing import List, Optional, Annotated
from datetime import datetime, timezone, timedelta
from bson import ObjectId
import logging
import bcrypt
import jwt

# ---------------------------------------------------------------- DB
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

JWT_ALGORITHM = "HS256"

# ---------------------------------------------------------------- Helpers
PyObjectId = Annotated[str, BeforeValidator(str)]


def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")


def verify_password(plain: str, hashed: str) -> bool:
    try:
        return bcrypt.checkpw(plain.encode("utf-8"), hashed.encode("utf-8"))
    except Exception:
        return False


def get_jwt_secret() -> str:
    return os.environ["JWT_SECRET"]


def create_access_token(user_id: str, email: str) -> str:
    payload = {"sub": user_id, "email": email,
               "exp": datetime.now(timezone.utc) + timedelta(hours=12), "type": "access"}
    return jwt.encode(payload, get_jwt_secret(), algorithm=JWT_ALGORITHM)


async def get_current_user(request: Request) -> dict:
    token = request.cookies.get("access_token")
    if not token:
        auth = request.headers.get("Authorization", "")
        if auth.startswith("Bearer "):
            token = auth[7:]
    if not token:
        raise HTTPException(status_code=401, detail="Not authenticated")
    try:
        payload = jwt.decode(token, get_jwt_secret(), algorithms=[JWT_ALGORITHM])
        user = await db.users.find_one({"_id": ObjectId(payload["sub"])})
        if not user:
            raise HTTPException(status_code=401, detail="User not found")
        user["id"] = str(user["_id"])
        user.pop("_id", None)
        user.pop("password_hash", None)
        return user
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail="Token expired")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail="Invalid token")


def serialize(doc: dict) -> dict:
    doc["id"] = str(doc.pop("_id"))
    return doc


# ---------------------------------------------------------------- Models
class LoginInput(BaseModel):
    email: str
    password: str


class VehicleBase(BaseModel):
    make: str
    model: str
    year: int
    price: float
    mileage: int = 0
    fuel: str = "Benzine"          # Benzine, Diesel, Elektrisch, Hybride
    transmission: str = "Handgeschakeld"
    body_type: str = "Sedan"
    color: str = "Zwart"
    description: str = ""
    images: List[str] = []
    featured: bool = False
    sold: bool = False


class VehicleCreate(VehicleBase):
    pass


class AppointmentCreate(BaseModel):
    name: str
    email: str
    phone: str
    service: str
    date: str
    time: str = ""
    car_info: str = ""
    message: str = ""


class ContactCreate(BaseModel):
    name: str
    email: str
    phone: str = ""
    subject: str = ""
    message: str


# ---------------------------------------------------------------- App
app = FastAPI()
api = APIRouter(prefix="/api")


@api.get("/")
async def root():
    return {"message": "AutoGarage API"}


# -------- Auth
@api.post("/auth/login")
async def login(data: LoginInput, response: Response):
    email = data.email.lower().strip()
    user = await db.users.find_one({"email": email})
    if not user or not verify_password(data.password, user["password_hash"]):
        raise HTTPException(status_code=401, detail="Ongeldige inloggegevens")
    token = create_access_token(str(user["_id"]), email)
    response.set_cookie(key="access_token", value=token, httponly=True,
                        secure=True, samesite="none", max_age=43200, path="/")
    return {"id": str(user["_id"]), "email": email, "name": user.get("name"), "role": user.get("role"), "token": token}


@api.post("/auth/logout")
async def logout(response: Response):
    response.delete_cookie("access_token", path="/")
    return {"message": "Uitgelogd"}


@api.get("/auth/me")
async def me(user: dict = Depends(get_current_user)):
    return user


# -------- Vehicles
@api.get("/vehicles")
async def list_vehicles(make: Optional[str] = None, fuel: Optional[str] = None,
                        body_type: Optional[str] = None, featured: Optional[bool] = None,
                        max_price: Optional[float] = None, search: Optional[str] = None):
    q: dict = {}
    if make:
        q["make"] = make
    if fuel:
        q["fuel"] = fuel
    if body_type:
        q["body_type"] = body_type
    if featured is not None:
        q["featured"] = featured
    if max_price:
        q["price"] = {"$lte": max_price}
    if search:
        q["$or"] = [{"make": {"$regex": search, "$options": "i"}},
                    {"model": {"$regex": search, "$options": "i"}}]
    docs = await db.vehicles.find(q).sort("created_at", -1).to_list(500)
    return [serialize(d) for d in docs]


@api.get("/vehicles/makes")
async def vehicle_makes():
    return await db.vehicles.distinct("make")


@api.get("/vehicles/{vid}")
async def get_vehicle(vid: str):
    doc = await db.vehicles.find_one({"_id": ObjectId(vid)})
    if not doc:
        raise HTTPException(status_code=404, detail="Voertuig niet gevonden")
    return serialize(doc)


@api.post("/vehicles")
async def create_vehicle(data: VehicleCreate, user: dict = Depends(get_current_user)):
    doc = data.model_dump()
    doc["created_at"] = datetime.now(timezone.utc).isoformat()
    res = await db.vehicles.insert_one(doc)
    doc["_id"] = res.inserted_id
    return serialize(doc)


@api.put("/vehicles/{vid}")
async def update_vehicle(vid: str, data: VehicleCreate, user: dict = Depends(get_current_user)):
    await db.vehicles.update_one({"_id": ObjectId(vid)}, {"$set": data.model_dump()})
    doc = await db.vehicles.find_one({"_id": ObjectId(vid)})
    if not doc:
        raise HTTPException(status_code=404, detail="Voertuig niet gevonden")
    return serialize(doc)


@api.delete("/vehicles/{vid}")
async def delete_vehicle(vid: str, user: dict = Depends(get_current_user)):
    await db.vehicles.delete_one({"_id": ObjectId(vid)})
    return {"message": "Verwijderd"}


# -------- Appointments
@api.post("/appointments")
async def create_appointment(data: AppointmentCreate):
    doc = data.model_dump()
    doc["status"] = "in_afwachting"
    doc["created_at"] = datetime.now(timezone.utc).isoformat()
    res = await db.appointments.insert_one(doc)
    doc["_id"] = res.inserted_id
    return serialize(doc)


@api.get("/appointments")
async def list_appointments(user: dict = Depends(get_current_user)):
    docs = await db.appointments.find().sort("created_at", -1).to_list(500)
    return [serialize(d) for d in docs]


@api.put("/appointments/{aid}/status")
async def update_appointment_status(aid: str, body: dict, user: dict = Depends(get_current_user)):
    await db.appointments.update_one({"_id": ObjectId(aid)}, {"$set": {"status": body.get("status")}})
    return {"message": "Bijgewerkt"}


@api.delete("/appointments/{aid}")
async def delete_appointment(aid: str, user: dict = Depends(get_current_user)):
    await db.appointments.delete_one({"_id": ObjectId(aid)})
    return {"message": "Verwijderd"}


# -------- Contact
@api.post("/contact")
async def create_contact(data: ContactCreate):
    doc = data.model_dump()
    doc["read"] = False
    doc["created_at"] = datetime.now(timezone.utc).isoformat()
    res = await db.contacts.insert_one(doc)
    doc["_id"] = res.inserted_id
    return serialize(doc)


@api.get("/contact")
async def list_contacts(user: dict = Depends(get_current_user)):
    docs = await db.contacts.find().sort("created_at", -1).to_list(500)
    return [serialize(d) for d in docs]


@api.delete("/contact/{cid}")
async def delete_contact(cid: str, user: dict = Depends(get_current_user)):
    await db.contacts.delete_one({"_id": ObjectId(cid)})
    return {"message": "Verwijderd"}


# -------- Admin stats
@api.get("/admin/stats")
async def admin_stats(user: dict = Depends(get_current_user)):
    total = await db.vehicles.count_documents({})
    sold = await db.vehicles.count_documents({"sold": True})
    appts = await db.appointments.count_documents({})
    pending = await db.appointments.count_documents({"status": "in_afwachting"})
    msgs = await db.contacts.count_documents({})
    return {"vehicles": total, "sold": sold, "appointments": appts,
            "pending_appointments": pending, "messages": msgs}


app.include_router(api)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


SAMPLE_VEHICLES = [
    {"make": "Mercedes-Benz", "model": "C-Klasse Coupé", "year": 2022, "price": 42900, "mileage": 28000,
     "fuel": "Benzine", "transmission": "Automaat", "body_type": "Coupé", "color": "Wit",
     "description": "Prachtige Mercedes C-Klasse Coupé in showroomstaat. Volledige onderhoudshistorie.",
     "images": ["https://images.unsplash.com/photo-1618642624018-a370cbf3cd80?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjA1ODh8MHwxfHNlYXJjaHwyfHxtb2Rlcm4lMjBsdXh1cnklMjBjYXIlMjBzaG93cm9vbSUyMGJyaWdodHxlbnwwfHx8fDE3ODM4MzExNTl8MA&ixlib=rb-4.1.0&q=85"],
     "featured": True, "sold": False},
    {"make": "Porsche", "model": "911 Carrera", "year": 2021, "price": 118500, "mileage": 15000,
     "fuel": "Benzine", "transmission": "Automaat", "body_type": "Coupé", "color": "Groen",
     "description": "Iconische Porsche 911 Carrera. Sportief en tijdloos design.",
     "images": ["https://images.unsplash.com/photo-1619284111834-34efc7051f0e?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjA1ODh8MHwxfHNlYXJjaHwzfHxtb2Rlcm4lMjBsdXh1cnklMjBjYXIlMjBzaG93cm9vbSUyMGJyaWdodHxlbnwwfHx8fDE3ODM4MzExNTl8MA&ixlib=rb-4.1.0&q=85"],
     "featured": True, "sold": False},
    {"make": "Audi", "model": "RS5 Sportback", "year": 2023, "price": 89900, "mileage": 9500,
     "fuel": "Benzine", "transmission": "Automaat", "body_type": "Sedan", "color": "Rood",
     "description": "Krachtige Audi RS5 met quattro aandrijving. Bijna nieuw.",
     "images": ["https://images.unsplash.com/photo-1644749700856-a82a92828a1b?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjA1ODh8MHwxfHNlYXJjaHwxfHxtb2Rlcm4lMjBsdXh1cnklMjBjYXIlMjBzaG93cm9vbSUyMGJyaWdodHxlbnwwfHx8fDE3ODM4MzExNTl8MA&ixlib=rb-4.1.0&q=85"],
     "featured": True, "sold": False},
    {"make": "Tesla", "model": "Model 3 Long Range", "year": 2023, "price": 46500, "mileage": 12000,
     "fuel": "Elektrisch", "transmission": "Automaat", "body_type": "Sedan", "color": "Wit",
     "description": "Volledig elektrische Tesla Model 3 met groot bereik en Autopilot.",
     "images": ["https://images.unsplash.com/photo-1763625903516-7346f11b3a01?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1Nzh8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBhdXRvJTIwbWVjaGFuaWMlMjB3b3JraW5nJTIwY2xlYW4lMjBnYXJhZ2V8ZW58MHx8fHwxNzgzODMxMTU5fDA&ixlib=rb-4.1.0&q=85"],
     "featured": False, "sold": False},
    {"make": "BMW", "model": "M4 Competition", "year": 2022, "price": 96500, "mileage": 18000,
     "fuel": "Benzine", "transmission": "Automaat", "body_type": "Coupé", "color": "Blauw",
     "description": "BMW M4 Competition met M Performance pakket. Adembenemende prestaties.",
     "images": ["https://images.unsplash.com/photo-1775687902926-3244299ee7d4?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1Nzh8MHwxfHNlYXJjaHw0fHxwcm9mZXNzaW9uYWwlMjBhdXRvJTIwbWVjaGFuaWMlMjB3b3JraW5nJTIwY2xlYW4lMjBnYXJhZ2V8ZW58MHx8fHwxNzgzODMxMTU5fDA&ixlib=rb-4.1.0&q=85"],
     "featured": True, "sold": False},
    {"make": "Volkswagen", "model": "Golf GTI", "year": 2021, "price": 34900, "mileage": 32000,
     "fuel": "Benzine", "transmission": "Handgeschakeld", "body_type": "Hatchback", "color": "Grijs",
     "description": "De legendarische Golf GTI. Sportief, praktisch en betrouwbaar.",
     "images": ["https://images.unsplash.com/photo-1545276133-d91bcba0803c?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjA1ODh8MHwxfHNlYXJjaHw0fHxtb2Rlcm4lMjBsdXh1cnklMjBjYXIlMjBzaG93cm9vbSUyMGJyaWdodHxlbnwwfHx8fDE3ODM4MzExNTl8MA&ixlib=rb-4.1.0&q=85"],
     "featured": False, "sold": False},
]


@app.on_event("startup")
async def startup():
    await db.users.create_index("email", unique=True)
    admin_email = os.environ["ADMIN_EMAIL"].lower()
    admin_password = os.environ["ADMIN_PASSWORD"]
    existing = await db.users.find_one({"email": admin_email})
    if existing is None:
        await db.users.insert_one({"email": admin_email, "password_hash": hash_password(admin_password),
                                   "name": "Beheerder", "role": "admin",
                                   "created_at": datetime.now(timezone.utc).isoformat()})
        logger.info("Admin seeded")
    elif not verify_password(admin_password, existing["password_hash"]):
        await db.users.update_one({"email": admin_email},
                                  {"$set": {"password_hash": hash_password(admin_password)}})
    if await db.vehicles.count_documents({}) == 0:
        for v in SAMPLE_VEHICLES:
            v["created_at"] = datetime.now(timezone.utc).isoformat()
        await db.vehicles.insert_many(SAMPLE_VEHICLES)
        logger.info("Sample vehicles seeded")


@app.on_event("shutdown")
async def shutdown():
    client.close()
