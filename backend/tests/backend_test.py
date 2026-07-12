"""AutoGarage backend tests - vehicles, appointments, contact, auth, admin"""
import os
import pytest
import requests

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "https://vehicle-care-pro-24.preview.emergentagent.com").rstrip("/")
API = f"{BASE_URL}/api"

ADMIN_EMAIL = "admin@autogarage.nl"
ADMIN_PASSWORD = "Admin@2026"


# -------- Fixtures
@pytest.fixture()
def api_client():
    """Fresh unauthenticated session per test (no cookie carryover)."""
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json"})
    return s


@pytest.fixture(scope="session")
def auth_token():
    s = requests.Session()
    r = s.post(f"{API}/auth/login", json={"email": ADMIN_EMAIL, "password": ADMIN_PASSWORD})
    if r.status_code != 200:
        pytest.skip(f"Login failed: {r.status_code} {r.text}")
    data = r.json()
    assert "token" in data and len(data["token"]) > 0
    return data["token"]


@pytest.fixture()
def auth_client(auth_token):
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json", "Authorization": f"Bearer {auth_token}"})
    return s


# -------- Health
class TestHealth:
    def test_root(self, api_client):
        r = api_client.get(f"{API}/")
        assert r.status_code == 200
        assert "message" in r.json()


# -------- Auth
class TestAuth:
    def test_login_success(self, api_client):
        r = api_client.post(f"{API}/auth/login", json={"email": ADMIN_EMAIL, "password": ADMIN_PASSWORD})
        assert r.status_code == 200
        d = r.json()
        assert d["email"] == ADMIN_EMAIL
        assert d["role"] == "admin"
        assert isinstance(d["token"], str) and len(d["token"]) > 20

    def test_login_invalid(self, api_client):
        r = api_client.post(f"{API}/auth/login", json={"email": ADMIN_EMAIL, "password": "wrong"})
        assert r.status_code == 401

    def test_login_unknown_user(self, api_client):
        r = api_client.post(f"{API}/auth/login", json={"email": "nobody@x.com", "password": "x"})
        assert r.status_code == 401

    def test_me_requires_auth(self, api_client):
        r = api_client.get(f"{API}/auth/me")
        assert r.status_code == 401

    def test_me_with_token(self, auth_client):
        r = auth_client.get(f"{API}/auth/me")
        assert r.status_code == 200
        d = r.json()
        assert d["email"] == ADMIN_EMAIL
        assert "password_hash" not in d


# -------- Vehicles (public list & detail)
class TestVehiclesPublic:
    def test_list_vehicles(self, api_client):
        r = api_client.get(f"{API}/vehicles")
        assert r.status_code == 200
        data = r.json()
        assert isinstance(data, list) and len(data) >= 1
        v = data[0]
        assert "id" in v and "make" in v and "model" in v
        assert "_id" not in v  # ObjectId excluded

    def test_list_featured(self, api_client):
        r = api_client.get(f"{API}/vehicles", params={"featured": "true"})
        assert r.status_code == 200
        for v in r.json():
            assert v["featured"] is True

    def test_filter_by_make(self, api_client):
        r = api_client.get(f"{API}/vehicles", params={"make": "BMW"})
        assert r.status_code == 200
        for v in r.json():
            assert v["make"] == "BMW"

    def test_filter_by_fuel(self, api_client):
        r = api_client.get(f"{API}/vehicles", params={"fuel": "Elektrisch"})
        assert r.status_code == 200
        for v in r.json():
            assert v["fuel"] == "Elektrisch"

    def test_search(self, api_client):
        r = api_client.get(f"{API}/vehicles", params={"search": "Golf"})
        assert r.status_code == 200
        assert len(r.json()) >= 1

    def test_makes_endpoint(self, api_client):
        r = api_client.get(f"{API}/vehicles/makes")
        assert r.status_code == 200
        assert isinstance(r.json(), list)

    def test_get_vehicle_detail(self, api_client):
        listing = api_client.get(f"{API}/vehicles").json()
        vid = listing[0]["id"]
        r = api_client.get(f"{API}/vehicles/{vid}")
        assert r.status_code == 200
        assert r.json()["id"] == vid

    def test_get_vehicle_bad_id(self, api_client):
        r = api_client.get(f"{API}/vehicles/507f1f77bcf86cd799439011")
        assert r.status_code == 404


# -------- Vehicles admin CRUD
class TestVehiclesAdmin:
    def test_create_requires_auth(self, api_client):
        r = api_client.post(f"{API}/vehicles", json={"make": "TEST", "model": "X", "year": 2024, "price": 1000})
        assert r.status_code == 401

    def test_full_crud(self, auth_client):
        payload = {"make": "TEST_Make", "model": "TEST_Model", "year": 2024,
                   "price": 12345.0, "mileage": 100, "fuel": "Benzine",
                   "transmission": "Automaat", "body_type": "Sedan", "color": "Zwart",
                   "description": "TEST vehicle", "images": [], "featured": False, "sold": False}
        r = auth_client.post(f"{API}/vehicles", json=payload)
        assert r.status_code == 200, r.text
        created = r.json()
        assert created["make"] == "TEST_Make"
        assert created["price"] == 12345.0
        vid = created["id"]

        # GET verify
        r2 = auth_client.get(f"{API}/vehicles/{vid}")
        assert r2.status_code == 200 and r2.json()["make"] == "TEST_Make"

        # UPDATE
        payload["price"] = 22222.0
        payload["sold"] = True
        r3 = auth_client.put(f"{API}/vehicles/{vid}", json=payload)
        assert r3.status_code == 200
        assert r3.json()["price"] == 22222.0
        assert r3.json()["sold"] is True

        # GET verify update
        r4 = auth_client.get(f"{API}/vehicles/{vid}")
        assert r4.json()["price"] == 22222.0

        # DELETE
        r5 = auth_client.delete(f"{API}/vehicles/{vid}")
        assert r5.status_code == 200

        # verify deleted
        r6 = auth_client.get(f"{API}/vehicles/{vid}")
        assert r6.status_code == 404


# -------- Appointments
class TestAppointments:
    def test_create_appointment_public(self, api_client):
        payload = {"name": "TEST User", "email": "test@ex.com", "phone": "+31612345678",
                   "service": "APK Keuring", "date": "2026-02-10", "time": "10:00",
                   "car_info": "TEST_car", "message": "TEST_msg"}
        r = api_client.post(f"{API}/appointments", json=payload)
        assert r.status_code == 200
        d = r.json()
        assert d["name"] == "TEST User"
        assert d["status"] == "in_afwachting"
        assert "id" in d

    def test_list_appointments_requires_auth(self, api_client):
        r = api_client.get(f"{API}/appointments")
        assert r.status_code == 401

    def test_list_appointments_authed(self, auth_client):
        r = auth_client.get(f"{API}/appointments")
        assert r.status_code == 200
        assert isinstance(r.json(), list)

    def test_status_update_flow(self, api_client, auth_client):
        payload = {"name": "TEST Status", "email": "s@ex.com", "phone": "1",
                   "service": "Onderhoud", "date": "2026-02-11"}
        create = api_client.post(f"{API}/appointments", json=payload)
        aid = create.json()["id"]

        # unauth cannot update
        r0 = api_client.put(f"{API}/appointments/{aid}/status", json={"status": "bevestigd"})
        assert r0.status_code == 401

        r = auth_client.put(f"{API}/appointments/{aid}/status", json={"status": "bevestigd"})
        assert r.status_code == 200

        # verify status persisted
        docs = auth_client.get(f"{API}/appointments").json()
        found = [d for d in docs if d["id"] == aid]
        assert found and found[0]["status"] == "bevestigd"

        auth_client.delete(f"{API}/appointments/{aid}")


# -------- Contact
class TestContact:
    def test_create_contact_public(self, api_client):
        payload = {"name": "TEST Contact", "email": "c@ex.com", "phone": "1",
                   "subject": "TEST", "message": "TEST message"}
        r = api_client.post(f"{API}/contact", json=payload)
        assert r.status_code == 200
        d = r.json()
        assert d["name"] == "TEST Contact"
        assert d["read"] is False

    def test_list_contact_requires_auth(self, api_client):
        r = api_client.get(f"{API}/contact")
        assert r.status_code == 401

    def test_list_contact_authed(self, auth_client):
        r = auth_client.get(f"{API}/contact")
        assert r.status_code == 200
        assert isinstance(r.json(), list)


# -------- Admin stats
class TestAdminStats:
    def test_stats_requires_auth(self, api_client):
        r = api_client.get(f"{API}/admin/stats")
        assert r.status_code == 401

    def test_stats(self, auth_client):
        r = auth_client.get(f"{API}/admin/stats")
        assert r.status_code == 200
        d = r.json()
        for k in ("vehicles", "sold", "appointments", "pending_appointments", "messages"):
            assert k in d
            assert isinstance(d[k], int)
