import React, { useState, useEffect, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { Car, LogOut, LayoutDashboard, CalendarDays, MessageSquare, Plus, Pencil, Trash2, Tag } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import { useAuth } from "../context/AuthContext";
import api, { formatApiError } from "../lib/api";
import { formatPrice } from "../components/VehicleCard";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Textarea } from "../components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "../components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import { Switch } from "../components/ui/switch";

const emptyVehicle = {
  make: "", model: "", year: 2023, price: 0, mileage: 0, fuel: "Benzine",
  transmission: "Automaat", body_type: "Sedan", color: "", description: "",
  images: [], featured: false, sold: false,
};

export default function Admin() {
  const { t } = useLang();
  const { user, loading, logout } = useAuth();
  const navigate = useNavigate();
  const [tab, setTab] = useState("dashboard");
  const [stats, setStats] = useState(null);
  const [vehicles, setVehicles] = useState([]);
  const [appts, setAppts] = useState([]);
  const [msgs, setMsgs] = useState([]);
  const [dialog, setDialog] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(emptyVehicle);
  const [imagesText, setImagesText] = useState("");

  const loadAll = useCallback(() => {
    api.get("/admin/stats").then((r) => setStats(r.data)).catch(() => {});
    api.get("/vehicles").then((r) => setVehicles(r.data));
    api.get("/appointments").then((r) => setAppts(r.data));
    api.get("/contact").then((r) => setMsgs(r.data));
  }, []);

  useEffect(() => {
    if (loading) return;
    if (!user) { navigate("/login"); return; }
    loadAll();
  }, [user, loading, navigate, loadAll]);

  if (loading || !user) return <div className="grid place-items-center min-h-screen text-muted-foreground">{t("common.loading")}</div>;

  const openNew = () => { setEditing(null); setForm(emptyVehicle); setImagesText(""); setDialog(true); };
  const openEdit = (v) => { setEditing(v); setForm(v); setImagesText((v.images || []).join("\n")); setDialog(true); };

  const saveVehicle = async () => {
    const payload = {
      ...form,
      year: Number(form.year), price: Number(form.price), mileage: Number(form.mileage),
      images: imagesText.split("\n").map((s) => s.trim()).filter(Boolean),
    };
    try {
      if (editing) await api.put(`/vehicles/${editing.id}`, payload);
      else await api.post("/vehicles", payload);
      toast.success(t("admin.save"));
      setDialog(false);
      loadAll();
    } catch (err) {
      toast.error(formatApiError(err.response?.data?.detail));
    }
  };

  const removeVehicle = async (id) => {
    await api.delete(`/vehicles/${id}`);
    toast.success(t("admin.delete"));
    loadAll();
  };

  const setApptStatus = async (id, status) => {
    await api.put(`/appointments/${id}/status`, { status });
    loadAll();
  };
  const removeAppt = async (id) => { await api.delete(`/appointments/${id}`); loadAll(); };
  const removeMsg = async (id) => { await api.delete(`/contact/${id}`); loadAll(); };

  const setF = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const nav = [
    { id: "dashboard", label: t("admin.dashboard"), icon: LayoutDashboard },
    { id: "vehicles", label: t("admin.vehicles"), icon: Car },
    { id: "appointments", label: t("admin.appointments"), icon: CalendarDays },
    { id: "messages", label: t("admin.messages"), icon: MessageSquare },
  ];

  const statusColor = { in_afwachting: "bg-amber-100 text-amber-800", bevestigd: "bg-emerald-100 text-emerald-800", afgerond: "bg-blue-100 text-blue-800", geannuleerd: "bg-red-100 text-red-800" };

  return (
    <div className="min-h-screen flex bg-muted/30" data-testid="admin-page">
      {/* sidebar */}
      <aside className="w-60 shrink-0 bg-card border-r border-border/60 hidden md:flex flex-col p-4">
        <div className="flex items-center gap-2.5 px-2 py-3 mb-4">
          <span className="h-8 w-8 rounded-lg bg-primary text-primary-foreground grid place-items-center"><Car className="h-4 w-4" /></span>
          <span className="font-display font-extrabold tracking-tight">AutoGarage</span>
        </div>
        <nav className="space-y-1 flex-1">
          {nav.map((n) => (
            <button key={n.id} onClick={() => setTab(n.id)} data-testid={`admin-tab-${n.id}`}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors ${tab === n.id ? "bg-primary text-primary-foreground" : "hover:bg-muted text-foreground/70"}`}>
              <n.icon className="h-4 w-4" /> {n.label}
            </button>
          ))}
        </nav>
        <Button variant="ghost" onClick={() => { logout(); navigate("/"); }} className="justify-start gap-3 rounded-xl" data-testid="admin-logout">
          <LogOut className="h-4 w-4" /> {t("admin.logout")}
        </Button>
      </aside>

      <main className="flex-1 p-5 md:p-8 overflow-x-auto">
        {/* mobile tabs */}
        <div className="md:hidden flex gap-2 mb-6 overflow-x-auto">
          {nav.map((n) => (
            <button key={n.id} onClick={() => setTab(n.id)} className={`px-3 py-2 rounded-full text-sm whitespace-nowrap ${tab === n.id ? "bg-primary text-primary-foreground" : "bg-card border"}`}>{n.label}</button>
          ))}
        </div>

        {tab === "dashboard" && stats && (
          <div data-testid="admin-dashboard">
            <h1 className="font-display text-3xl font-extrabold tracking-tight mb-8">{t("admin.dashboard")}</h1>
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {[
                { l: t("admin.totalVehicles"), v: stats.vehicles, icon: Car },
                { l: t("admin.soldCount"), v: stats.sold, icon: Tag },
                { l: t("admin.appointments"), v: stats.appointments, icon: CalendarDays },
                { l: t("admin.pending"), v: stats.pending_appointments, icon: MessageSquare },
              ].map((s) => (
                <div key={s.l} className="rounded-2xl bg-card border border-border/60 p-6">
                  <s.icon className="h-6 w-6 text-primary mb-4" />
                  <p className="font-display text-4xl font-extrabold tracking-tighter">{s.v}</p>
                  <p className="text-sm text-muted-foreground mt-1">{s.l}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {tab === "vehicles" && (
          <div data-testid="admin-vehicles">
            <div className="flex items-center justify-between mb-6">
              <h1 className="font-display text-3xl font-extrabold tracking-tight">{t("admin.vehicles")}</h1>
              <Button onClick={openNew} className="rounded-full gap-2" data-testid="add-vehicle-btn"><Plus className="h-4 w-4" /> {t("admin.addVehicle")}</Button>
            </div>
            <div className="rounded-2xl bg-card border border-border/60 overflow-hidden">
              <Table>
                <TableHeader><TableRow>
                  <TableHead>Auto</TableHead><TableHead>{t("detail.year")}</TableHead><TableHead>Prijs</TableHead>
                  <TableHead>{t("admin.status")}</TableHead><TableHead className="text-right">Acties</TableHead>
                </TableRow></TableHeader>
                <TableBody>
                  {vehicles.map((v) => (
                    <TableRow key={v.id} data-testid={`admin-vehicle-row-${v.id}`}>
                      <TableCell className="font-medium">{v.make} {v.model}</TableCell>
                      <TableCell>{v.year}</TableCell>
                      <TableCell>{formatPrice(v.price)}</TableCell>
                      <TableCell>{v.sold ? <Badge variant="destructive">{t("inventory.sold")}</Badge> : v.featured ? <Badge>{t("inventory.featured")}</Badge> : <Badge variant="secondary">—</Badge>}</TableCell>
                      <TableCell className="text-right">
                        <Button size="icon" variant="ghost" onClick={() => openEdit(v)} data-testid={`edit-vehicle-${v.id}`}><Pencil className="h-4 w-4" /></Button>
                        <Button size="icon" variant="ghost" onClick={() => removeVehicle(v.id)} data-testid={`delete-vehicle-${v.id}`}><Trash2 className="h-4 w-4 text-destructive" /></Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        )}

        {tab === "appointments" && (
          <div data-testid="admin-appointments">
            <h1 className="font-display text-3xl font-extrabold tracking-tight mb-6">{t("admin.appointments")}</h1>
            <div className="rounded-2xl bg-card border border-border/60 overflow-hidden">
              <Table>
                <TableHeader><TableRow>
                  <TableHead>{t("booking.name")}</TableHead><TableHead>{t("booking.service")}</TableHead><TableHead>{t("booking.date")}</TableHead>
                  <TableHead>{t("admin.status")}</TableHead><TableHead className="text-right">Acties</TableHead>
                </TableRow></TableHeader>
                <TableBody>
                  {appts.map((a) => (
                    <TableRow key={a.id} data-testid={`appt-row-${a.id}`}>
                      <TableCell><p className="font-medium">{a.name}</p><p className="text-xs text-muted-foreground">{a.phone}</p></TableCell>
                      <TableCell>{a.service}</TableCell>
                      <TableCell>{a.date} {a.time}</TableCell>
                      <TableCell>
                        <Select value={a.status} onValueChange={(v) => setApptStatus(a.id, v)}>
                          <SelectTrigger className={`h-8 w-36 rounded-full border-0 text-xs ${statusColor[a.status] || ""}`} data-testid={`appt-status-${a.id}`}><SelectValue /></SelectTrigger>
                          <SelectContent className="bg-popover">
                            {["in_afwachting", "bevestigd", "afgerond", "geannuleerd"].map((s) => <SelectItem key={s} value={s}>{s.replace("_", " ")}</SelectItem>)}
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell className="text-right"><Button size="icon" variant="ghost" onClick={() => removeAppt(a.id)}><Trash2 className="h-4 w-4 text-destructive" /></Button></TableCell>
                    </TableRow>
                  ))}
                  {appts.length === 0 && <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground py-10">—</TableCell></TableRow>}
                </TableBody>
              </Table>
            </div>
          </div>
        )}

        {tab === "messages" && (
          <div data-testid="admin-messages">
            <h1 className="font-display text-3xl font-extrabold tracking-tight mb-6">{t("admin.messages")}</h1>
            <div className="grid gap-4 md:grid-cols-2">
              {msgs.map((m) => (
                <div key={m.id} className="rounded-2xl bg-card border border-border/60 p-6" data-testid={`msg-${m.id}`}>
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="font-semibold">{m.name}</p>
                      <p className="text-sm text-muted-foreground">{m.email} · {m.phone}</p>
                    </div>
                    <Button size="icon" variant="ghost" onClick={() => removeMsg(m.id)}><Trash2 className="h-4 w-4 text-destructive" /></Button>
                  </div>
                  {m.subject && <p className="font-medium mt-3">{m.subject}</p>}
                  <p className="text-sm text-foreground/70 mt-2 leading-relaxed">{m.message}</p>
                </div>
              ))}
              {msgs.length === 0 && <p className="text-muted-foreground">—</p>}
            </div>
          </div>
        )}
      </main>

      {/* vehicle dialog */}
      <Dialog open={dialog} onOpenChange={setDialog}>
        <DialogContent className="bg-popover max-w-2xl max-h-[90vh] overflow-y-auto" data-testid="vehicle-dialog">
          <DialogHeader><DialogTitle>{editing ? t("admin.edit") : t("admin.addVehicle")}</DialogTitle></DialogHeader>
          <div className="grid md:grid-cols-2 gap-4 py-2">
            <div><Label className="mb-1.5 block text-sm">Merk</Label><Input value={form.make} onChange={setF("make")} data-testid="vf-make" /></div>
            <div><Label className="mb-1.5 block text-sm">Model</Label><Input value={form.model} onChange={setF("model")} data-testid="vf-model" /></div>
            <div><Label className="mb-1.5 block text-sm">{t("detail.year")}</Label><Input type="number" value={form.year} onChange={setF("year")} data-testid="vf-year" /></div>
            <div><Label className="mb-1.5 block text-sm">Prijs (€)</Label><Input type="number" value={form.price} onChange={setF("price")} data-testid="vf-price" /></div>
            <div><Label className="mb-1.5 block text-sm">{t("detail.mileage")} (km)</Label><Input type="number" value={form.mileage} onChange={setF("mileage")} data-testid="vf-mileage" /></div>
            <div><Label className="mb-1.5 block text-sm">{t("detail.color")}</Label><Input value={form.color} onChange={setF("color")} data-testid="vf-color" /></div>
            <div>
              <Label className="mb-1.5 block text-sm">{t("detail.fuel")}</Label>
              <Select value={form.fuel} onValueChange={(v) => setForm({ ...form, fuel: v })}>
                <SelectTrigger data-testid="vf-fuel"><SelectValue /></SelectTrigger>
                <SelectContent className="bg-popover">{["Benzine", "Diesel", "Elektrisch", "Hybride"].map((x) => <SelectItem key={x} value={x}>{x}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div>
              <Label className="mb-1.5 block text-sm">{t("detail.transmission")}</Label>
              <Select value={form.transmission} onValueChange={(v) => setForm({ ...form, transmission: v })}>
                <SelectTrigger data-testid="vf-trans"><SelectValue /></SelectTrigger>
                <SelectContent className="bg-popover">{["Automaat", "Handgeschakeld"].map((x) => <SelectItem key={x} value={x}>{x}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div>
              <Label className="mb-1.5 block text-sm">{t("detail.body")}</Label>
              <Select value={form.body_type} onValueChange={(v) => setForm({ ...form, body_type: v })}>
                <SelectTrigger data-testid="vf-body"><SelectValue /></SelectTrigger>
                <SelectContent className="bg-popover">{["Sedan", "Coupé", "Hatchback", "SUV", "Stationwagon"].map((x) => <SelectItem key={x} value={x}>{x}</SelectItem>)}</SelectContent>
              </Select>
            </div>
          </div>
          <div><Label className="mb-1.5 block text-sm">{t("detail.description")}</Label><Textarea value={form.description} onChange={setF("description")} rows={3} data-testid="vf-desc" /></div>
          <div><Label className="mb-1.5 block text-sm">Afbeeldingen (één URL per regel)</Label><Textarea value={imagesText} onChange={(e) => setImagesText(e.target.value)} rows={3} data-testid="vf-images" /></div>
          <div className="flex items-center gap-8">
            <label className="flex items-center gap-2 text-sm"><Switch checked={form.featured} onCheckedChange={(v) => setForm({ ...form, featured: v })} data-testid="vf-featured" /> {t("inventory.featured")}</label>
            <label className="flex items-center gap-2 text-sm"><Switch checked={form.sold} onCheckedChange={(v) => setForm({ ...form, sold: v })} data-testid="vf-sold" /> {t("inventory.sold")}</label>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialog(false)} className="rounded-full">{t("admin.cancel")}</Button>
            <Button onClick={saveVehicle} className="rounded-full" data-testid="save-vehicle-btn">{t("admin.save")}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
