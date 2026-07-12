import React, { useState, useEffect } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { ArrowLeft, Calendar, Gauge, Fuel, Settings2, Palette, Car, CheckCircle2 } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import api from "../lib/api";
import { formatPrice } from "../components/VehicleCard";
import { Button } from "../components/ui/button";

export default function VehicleDetail() {
  const { id } = useParams();
  const { t } = useLang();
  const navigate = useNavigate();
  const [v, setV] = useState(null);
  const [active, setActive] = useState(0);

  useEffect(() => {
    api.get(`/vehicles/${id}`).then((r) => setV(r.data)).catch(() => navigate("/inventory"));
  }, [id, navigate]);

  if (!v) return <div className="max-w-7xl mx-auto px-5 py-20 text-muted-foreground">{t("common.loading")}</div>;

  const specs = [
    { icon: Calendar, label: t("detail.year"), value: v.year },
    { icon: Gauge, label: t("detail.mileage"), value: `${v.mileage.toLocaleString("nl-NL")} km` },
    { icon: Fuel, label: t("detail.fuel"), value: v.fuel },
    { icon: Settings2, label: t("detail.transmission"), value: v.transmission },
    { icon: Car, label: t("detail.body"), value: v.body_type },
    { icon: Palette, label: t("detail.color"), value: v.color },
  ];

  return (
    <div className="max-w-7xl mx-auto px-5 sm:px-8 py-10" data-testid="vehicle-detail-page">
      <Link to="/inventory" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-8" data-testid="back-link">
        <ArrowLeft className="h-4 w-4" /> {t("detail.back")}
      </Link>

      <div className="grid lg:grid-cols-2 gap-12">
        <div>
          <div className="rounded-2xl overflow-hidden bg-muted aspect-[4/3]">
            <img src={v.images?.[active]} alt={`${v.make} ${v.model}`} className="h-full w-full object-cover" />
          </div>
          {v.images?.length > 1 && (
            <div className="flex gap-3 mt-4">
              {v.images.map((img, i) => (
                <button key={i} onClick={() => setActive(i)} className={`h-20 w-24 rounded-xl overflow-hidden border-2 ${active === i ? "border-primary" : "border-transparent"}`}>
                  <img src={img} alt="" className="h-full w-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        <div>
          {v.featured && <span className="text-xs font-semibold px-3 py-1 rounded-full bg-accent text-accent-foreground">{t("inventory.featured")}</span>}
          <h1 className="font-display text-4xl font-extrabold tracking-tighter mt-4">{v.make} {v.model}</h1>
          <p className="font-display text-3xl font-extrabold text-primary mt-3">{formatPrice(v.price)}</p>

          <div className="grid grid-cols-2 gap-4 mt-8">
            {specs.map((s) => (
              <div key={s.label} className="rounded-xl bg-card border border-border/60 p-4">
                <span className="flex items-center gap-2 text-xs uppercase tracking-wider text-muted-foreground"><s.icon className="h-4 w-4" /> {s.label}</span>
                <p className="font-semibold mt-1.5">{s.value}</p>
              </div>
            ))}
          </div>

          <div className="mt-8 rounded-2xl bg-accent p-6">
            <p className="font-display font-bold text-lg text-accent-foreground flex items-center gap-2"><CheckCircle2 className="h-5 w-5" /> {t("detail.interested")}</p>
            <div className="flex flex-wrap gap-3 mt-4">
              <Button onClick={() => navigate("/booking")} className="rounded-full" data-testid="detail-book-btn">{t("detail.bookViewing")}</Button>
              <Button onClick={() => navigate("/contact")} variant="outline" className="rounded-full bg-background" data-testid="detail-contact-btn">{t("detail.contactBtn")}</Button>
            </div>
          </div>
        </div>
      </div>

      <div className="mt-14 max-w-3xl">
        <h2 className="font-display text-2xl font-bold tracking-tight mb-4">{t("detail.description")}</h2>
        <p className="text-foreground/70 leading-relaxed text-lg">{v.description}</p>
      </div>
    </div>
  );
}
