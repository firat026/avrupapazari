import React, { useState } from "react";
import { toast } from "sonner";
import { MapPin, Phone, Mail, Clock } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import api, { formatApiError } from "../lib/api";
import { Input } from "../components/ui/input";
import { Textarea } from "../components/ui/textarea";
import { Label } from "../components/ui/label";
import { Button } from "../components/ui/button";

const empty = { name: "", email: "", phone: "", subject: "", message: "" };

export default function Contact() {
  const { t } = useLang();
  const [form, setForm] = useState(empty);
  const [submitting, setSubmitting] = useState(false);
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.post("/contact", form);
      toast.success(t("contact.success"));
      setForm(empty);
    } catch (err) {
      toast.error(formatApiError(err.response?.data?.detail));
    } finally {
      setSubmitting(false);
    }
  };

  const L = "text-xs uppercase tracking-wider text-muted-foreground mb-1.5 block";
  const info = [
    { icon: MapPin, label: t("contact.address"), value: "Autoweg 24, 1012 AB Amsterdam" },
    { icon: Phone, label: t("contact.phone"), value: "+31 20 123 4567" },
    { icon: Mail, label: t("contact.email"), value: "info@autogarage.nl" },
    { icon: Clock, label: t("contact.hours"), value: t("contact.hoursValue") },
  ];

  return (
    <div className="max-w-7xl mx-auto px-5 sm:px-8 py-14" data-testid="contact-page">
      <div className="max-w-2xl mb-12">
        <h1 className="font-display text-4xl md:text-5xl font-extrabold tracking-tighter">{t("contact.title")}</h1>
        <p className="text-foreground/70 mt-3 text-lg">{t("contact.subtitle")}</p>
      </div>

      <div className="grid lg:grid-cols-2 gap-10">
        <form onSubmit={submit} className="rounded-2xl bg-card border border-border/60 p-6 md:p-8 space-y-5" data-testid="contact-form">
          <div className="grid md:grid-cols-2 gap-5">
            <div><Label className={L}>{t("contact.name")}</Label><Input required value={form.name} onChange={set("name")} className="rounded-xl" data-testid="contact-name" /></div>
            <div><Label className={L}>{t("contact.email")}</Label><Input required type="email" value={form.email} onChange={set("email")} className="rounded-xl" data-testid="contact-email" /></div>
          </div>
          <div className="grid md:grid-cols-2 gap-5">
            <div><Label className={L}>{t("contact.phone")}</Label><Input value={form.phone} onChange={set("phone")} className="rounded-xl" data-testid="contact-phone" /></div>
            <div><Label className={L}>{t("contact.subject")}</Label><Input value={form.subject} onChange={set("subject")} className="rounded-xl" data-testid="contact-subject" /></div>
          </div>
          <div><Label className={L}>{t("contact.message")}</Label><Textarea required value={form.message} onChange={set("message")} rows={5} className="rounded-xl" data-testid="contact-message" /></div>
          <Button type="submit" disabled={submitting} className="rounded-full w-full h-12 text-base" data-testid="contact-submit">
            {submitting ? t("common.loading") : t("contact.submit")}
          </Button>
        </form>

        <div className="space-y-5">
          {info.map((it) => (
            <div key={it.label} className="rounded-2xl bg-card border border-border/60 p-6 flex gap-4">
              <span className="h-11 w-11 rounded-xl bg-accent text-accent-foreground grid place-items-center shrink-0"><it.icon className="h-5 w-5" /></span>
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">{it.label}</p>
                <p className="font-medium mt-1">{it.value}</p>
              </div>
            </div>
          ))}
          <div className="rounded-2xl overflow-hidden border border-border/60 h-[220px]">
            <iframe title="map" className="w-full h-full grayscale" loading="lazy"
              src="https://www.openstreetmap.org/export/embed.html?bbox=4.88%2C52.36%2C4.92%2C52.38&layer=mapnik" />
          </div>
        </div>
      </div>
    </div>
  );
}
