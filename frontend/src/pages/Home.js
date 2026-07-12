import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { motion } from "framer-motion";
import { ArrowRight, Search, ShieldCheck, Wrench, Sparkles, BatteryCharging, Star } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import api from "../lib/api";
import { VehicleCard } from "../components/VehicleCard";
import { Button } from "../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";

const HERO_IMG = "https://images.unsplash.com/photo-1545276133-d91bcba0803c?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjA1ODh8MHwxfHNlYXJjaHw0fHxtb2Rlcm4lMjBsdXh1cnklMjBjYXIlMjBzaG93cm9vbSUyMGJyaWdodHxlbnwwfHx8fDE3ODM4MzExNTl8MA&ixlib=rb-4.1.0&q=85";

export default function Home() {
  const { t } = useLang();
  const navigate = useNavigate();
  const [featured, setFeatured] = useState([]);
  const [makes, setMakes] = useState([]);
  const [f, setF] = useState({ make: "", fuel: "", body_type: "" });

  useEffect(() => {
    api.get("/vehicles", { params: { featured: true } }).then((r) => setFeatured(r.data.slice(0, 3)));
    api.get("/vehicles/makes").then((r) => setMakes(r.data));
  }, []);

  const doSearch = () => {
    const p = new URLSearchParams();
    Object.entries(f).forEach(([k, v]) => v && p.set(k, v));
    navigate(`/inventory?${p.toString()}`);
  };

  const stats = [
    { v: "150+", l: t("stats.cars") },
    { v: "20", l: t("stats.years") },
    { v: "5.000+", l: t("stats.clients") },
    { v: "4.9/5", l: t("stats.rating") },
  ];

  const services = [
    { icon: ShieldCheck, title: t("services.diagnostic"), desc: t("services.diagnosticDesc") },
    { icon: Wrench, title: t("services.repair"), desc: t("services.repairDesc") },
    { icon: Sparkles, title: t("services.wash"), desc: t("services.washDesc") },
    { icon: BatteryCharging, title: t("services.ev"), desc: t("services.evDesc") },
  ];

  return (
    <div data-testid="home-page">
      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="max-w-7xl mx-auto px-5 sm:px-8 pt-16 pb-24 grid lg:grid-cols-2 gap-12 items-center">
          <div className="fade-up">
            <span className="inline-flex items-center gap-2 text-sm font-medium px-4 py-1.5 rounded-full bg-accent text-accent-foreground mb-6">
              <Star className="h-4 w-4" /> {t("hero.badge")}
            </span>
            <h1 className="font-display text-5xl md:text-6xl font-extrabold tracking-tighter leading-[1.05]">
              {t("hero.title")}<br />
              <span className="text-primary">{t("hero.titleAccent")}</span>
            </h1>
            <p className="mt-6 text-lg text-foreground/70 max-w-lg leading-relaxed">{t("hero.subtitle")}</p>
            <div className="flex flex-wrap gap-3 mt-8">
              <Button onClick={() => navigate("/inventory")} size="lg" className="rounded-full gap-2 hover:scale-[1.03] transition-transform" data-testid="hero-inventory-btn">
                {t("hero.cta1")} <ArrowRight className="h-4 w-4" />
              </Button>
              <Button onClick={() => navigate("/booking")} size="lg" variant="outline" className="rounded-full" data-testid="hero-booking-btn">
                {t("hero.cta2")}
              </Button>
            </div>
          </div>

          <motion.div initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.7 }} className="relative">
            <img src={HERO_IMG} alt="Showroom" className="rounded-3xl w-full aspect-[5/4] object-cover shadow-2xl" />
          </motion.div>
        </div>

        {/* Search bar */}
        <div className="max-w-5xl mx-auto px-5 sm:px-8 -mt-8 relative z-10">
          <div className="glass border border-border/60 rounded-2xl shadow-lg p-4 grid gap-3 md:grid-cols-4" data-testid="hero-search-bar">
            <Select value={f.make} onValueChange={(v) => setF({ ...f, make: v === "any" ? "" : v })}>
              <SelectTrigger className="rounded-xl bg-background" data-testid="search-make"><SelectValue placeholder={t("hero.searchMake")} /></SelectTrigger>
              <SelectContent className="bg-popover">
                <SelectItem value="any">{t("hero.any")}</SelectItem>
                {makes.map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
              </SelectContent>
            </Select>
            <Select value={f.fuel} onValueChange={(v) => setF({ ...f, fuel: v === "any" ? "" : v })}>
              <SelectTrigger className="rounded-xl bg-background" data-testid="search-fuel"><SelectValue placeholder={t("hero.searchFuel")} /></SelectTrigger>
              <SelectContent className="bg-popover">
                <SelectItem value="any">{t("hero.any")}</SelectItem>
                {["Benzine", "Diesel", "Elektrisch", "Hybride"].map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
              </SelectContent>
            </Select>
            <Select value={f.body_type} onValueChange={(v) => setF({ ...f, body_type: v === "any" ? "" : v })}>
              <SelectTrigger className="rounded-xl bg-background" data-testid="search-body"><SelectValue placeholder={t("hero.searchType")} /></SelectTrigger>
              <SelectContent className="bg-popover">
                <SelectItem value="any">{t("hero.any")}</SelectItem>
                {["Sedan", "Coupé", "Hatchback", "SUV", "Stationwagon"].map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
              </SelectContent>
            </Select>
            <Button onClick={doSearch} className="rounded-xl gap-2 h-auto" data-testid="search-submit"><Search className="h-4 w-4" /> {t("hero.searchBtn")}</Button>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section className="max-w-7xl mx-auto px-5 sm:px-8 py-20 grid grid-cols-2 md:grid-cols-4 gap-6">
        {stats.map((s) => (
          <div key={s.l} className="text-center">
            <p className="font-display text-4xl md:text-5xl font-extrabold text-primary tracking-tighter">{s.v}</p>
            <p className="text-sm text-muted-foreground mt-2">{s.l}</p>
          </div>
        ))}
      </section>

      {/* Services */}
      <section className="max-w-7xl mx-auto px-5 sm:px-8 py-8">
        <div className="max-w-2xl mb-12">
          <h2 className="font-display text-3xl md:text-4xl font-extrabold tracking-tight">{t("services.title")}</h2>
          <p className="text-foreground/70 mt-3 text-lg">{t("services.subtitle")}</p>
        </div>
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {services.map((s, i) => (
            <div key={i} className="rounded-2xl bg-card border border-border/60 p-7 hover:shadow-lg hover:-translate-y-1 transition-[transform,box-shadow] duration-300" data-testid={`service-card-${i}`}>
              <span className="h-12 w-12 rounded-xl bg-accent text-accent-foreground grid place-items-center mb-5">
                <s.icon className="h-6 w-6" />
              </span>
              <h3 className="font-display font-bold text-xl mb-2">{s.title}</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">{s.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Featured */}
      <section className="max-w-7xl mx-auto px-5 sm:px-8 py-20">
        <div className="flex items-end justify-between mb-12 flex-wrap gap-4">
          <div>
            <h2 className="font-display text-3xl md:text-4xl font-extrabold tracking-tight">{t("inventory.featured")}</h2>
            <p className="text-foreground/70 mt-3 text-lg">{t("inventory.subtitle")}</p>
          </div>
          <Button variant="outline" onClick={() => navigate("/inventory")} className="rounded-full gap-2" data-testid="view-all-btn">
            {t("hero.cta1")} <ArrowRight className="h-4 w-4" />
          </Button>
        </div>
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          {featured.map((v, i) => <VehicleCard key={v.id} v={v} index={i} />)}
        </div>
      </section>

      {/* CTA */}
      <section className="max-w-7xl mx-auto px-5 sm:px-8">
        <div className="rounded-3xl bg-primary text-primary-foreground p-10 md:p-16 flex flex-col md:flex-row items-center justify-between gap-8">
          <div>
            <h2 className="font-display text-3xl md:text-4xl font-extrabold tracking-tight">{t("services.bookNow")}</h2>
            <p className="opacity-90 mt-3 text-lg max-w-md">{t("booking.subtitle")}</p>
          </div>
          <Button onClick={() => navigate("/booking")} size="lg" variant="secondary" className="rounded-full gap-2 shrink-0" data-testid="cta-booking-btn">
            {t("nav.booking")} <ArrowRight className="h-4 w-4" />
          </Button>
        </div>
      </section>
    </div>
  );
}
