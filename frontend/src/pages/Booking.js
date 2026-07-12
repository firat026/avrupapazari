import React, { useState } from "react";
import { toast } from "sonner";
import { CalendarCheck } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import api, { formatApiError } from "../lib/api";
import { Input } from "../components/ui/input";
import { Textarea } from "../components/ui/textarea";
import { Label } from "../components/ui/label";
import { Button } from "../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { SERVICES_LIST } from "../data/translations";

const empty = { name: "", email: "", phone: "", service: "", date: "", time: "", car_info: "", message: "" };

export default function Booking() {
  const { t } = useLang();
  const [form, setForm] = useState(empty);
  const [submitting, setSubmitting] = useState(false);

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    if (!form.service) { toast.error(t("booking.selectService")); return; }
    setSubmitting(true);
    try {
      await api.post("/appointments", form);
      toast.success(t("booking.success"));
      setForm(empty);
    } catch (err) {
      toast.error(formatApiError(err.response?.data?.detail));
    } finally {
      setSubmitting(false);
    }
  };

  const L = "text-xs uppercase tracking-wider text-muted-foreground mb-1.5 block";

  return (
    <div className="max-w-3xl mx-auto px-5 sm:px-8 py-14" data-testid="booking-page">
      <div className="text-center mb-10">
        <span className="h-14 w-14 rounded-2xl bg-accent text-accent-foreground grid place-items-center mx-auto mb-5"><CalendarCheck className="h-7 w-7" /></span>
        <h1 className="font-display text-4xl md:text-5xl font-extrabold tracking-tighter">{t("booking.title")}</h1>
        <p className="text-foreground/70 mt-3 text-lg">{t("booking.subtitle")}</p>
      </div>

      <form onSubmit={submit} className="rounded-2xl bg-card border border-border/60 p-6 md:p-8 space-y-5" data-testid="booking-form">
        <div className="grid md:grid-cols-2 gap-5">
          <div><Label className={L}>{t("booking.name")}</Label><Input required value={form.name} onChange={set("name")} className="rounded-xl" data-testid="booking-name" /></div>
          <div><Label className={L}>{t("booking.email")}</Label><Input required type="email" value={form.email} onChange={set("email")} className="rounded-xl" data-testid="booking-email" /></div>
        </div>
        <div className="grid md:grid-cols-2 gap-5">
          <div><Label className={L}>{t("booking.phone")}</Label><Input required value={form.phone} onChange={set("phone")} className="rounded-xl" data-testid="booking-phone" /></div>
          <div>
            <Label className={L}>{t("booking.service")}</Label>
            <Select value={form.service} onValueChange={(v) => setForm({ ...form, service: v })}>
              <SelectTrigger className="rounded-xl" data-testid="booking-service"><SelectValue placeholder={t("booking.selectService")} /></SelectTrigger>
              <SelectContent className="bg-popover">{SERVICES_LIST.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}</SelectContent>
            </Select>
          </div>
        </div>
        <div className="grid md:grid-cols-2 gap-5">
          <div><Label className={L}>{t("booking.date")}</Label><Input required type="date" value={form.date} onChange={set("date")} className="rounded-xl" data-testid="booking-date" /></div>
          <div><Label className={L}>{t("booking.time")}</Label><Input type="time" value={form.time} onChange={set("time")} className="rounded-xl" data-testid="booking-time" /></div>
        </div>
        <div><Label className={L}>{t("booking.carInfo")}</Label><Input value={form.car_info} onChange={set("car_info")} className="rounded-xl" data-testid="booking-car" /></div>
        <div><Label className={L}>{t("booking.message")}</Label><Textarea value={form.message} onChange={set("message")} rows={4} className="rounded-xl" data-testid="booking-message" /></div>
        <Button type="submit" disabled={submitting} className="rounded-full w-full h-12 text-base" data-testid="booking-submit">
          {submitting ? t("common.loading") : t("booking.submit")}
        </Button>
      </form>
    </div>
  );
}
