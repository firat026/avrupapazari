import React from "react";
import { useNavigate } from "react-router-dom";
import { ShieldCheck, Wrench, Sparkles, BatteryCharging, ArrowRight } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import { Button } from "../components/ui/button";

const IMG = {
  diag: "https://images.unsplash.com/photo-1775687902926-3244299ee7d4?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1Nzh8MHwxfHNlYXJjaHw0fHxwcm9mZXNzaW9uYWwlMjBhdXRvJTIwbWVjaGFuaWMlMjB3b3JraW5nJTIwY2xlYW4lMjBnYXJhZ2V8ZW58MHx8fHwxNzgzODMxMTU5fDA&ixlib=rb-4.1.0&q=85",
  ev: "https://images.unsplash.com/photo-1763625903516-7346f11b3a01?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1Nzh8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBhdXRvJTIwbWVjaGFuaWMlMjB3b3JraW5nJTIwY2xlYW4lMjBnYXJhZ2V8ZW58MHx8fHwxNzgzODMxMTU5fDA&ixlib=rb-4.1.0&q=85",
  wash: "https://images.unsplash.com/photo-1782235869336-2370b783da4a?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NDk1Nzh8MHwxfHNlYXJjaHwzfHxwcm9mZXNzaW9uYWwlMjBhdXRvJTIwbWVjaGFuaWMlMjB3b3JraW5nJTIwY2xlYW4lMjBnYXJhZ2V8ZW58MHx8fHwxNzgzODMxMTU5fDA&ixlib=rb-4.1.0&q=85",
};

export default function Services() {
  const { t } = useLang();
  const navigate = useNavigate();

  const items = [
    { icon: ShieldCheck, title: t("services.diagnostic"), desc: t("services.diagnosticDesc"), img: IMG.diag },
    { icon: Wrench, title: t("services.repair"), desc: t("services.repairDesc") },
    { icon: Sparkles, title: t("services.wash"), desc: t("services.washDesc"), img: IMG.wash },
    { icon: BatteryCharging, title: t("services.ev"), desc: t("services.evDesc"), img: IMG.ev },
  ];

  return (
    <div className="max-w-7xl mx-auto px-5 sm:px-8 py-14" data-testid="services-page">
      <div className="max-w-2xl mb-14">
        <h1 className="font-display text-4xl md:text-5xl font-extrabold tracking-tighter">{t("services.title")}</h1>
        <p className="text-foreground/70 mt-4 text-lg">{t("services.subtitle")}</p>
      </div>

      <div className="grid gap-6 md:grid-cols-6 auto-rows-[minmax(0,1fr)]">
        {/* large */}
        <div className="md:col-span-4 rounded-3xl overflow-hidden relative min-h-[340px] group" data-testid="service-bento-0">
          <img src={items[0].img} alt="" className="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition-transform duration-500" />
          <div className="absolute inset-0 bg-gradient-to-t from-foreground/85 to-transparent" />
          <div className="relative h-full flex flex-col justify-end p-8 text-background">
            <ShieldCheck className="h-9 w-9 mb-3" />
            <h3 className="font-display text-2xl font-bold">{items[0].title}</h3>
            <p className="text-background/80 mt-2 max-w-md">{items[0].desc}</p>
          </div>
        </div>

        {/* tall CTA */}
        <div className="md:col-span-2 md:row-span-2 rounded-3xl bg-primary text-primary-foreground p-8 flex flex-col justify-between min-h-[340px]" data-testid="service-cta">
          <div>
            <h3 className="font-display text-2xl font-bold">{t("nav.booking")}</h3>
            <p className="opacity-90 mt-3">{t("booking.subtitle")}</p>
          </div>
          <Button onClick={() => navigate("/booking")} variant="secondary" className="rounded-full gap-2 mt-6 w-fit" data-testid="services-book-btn">
            {t("services.bookNow")} <ArrowRight className="h-4 w-4" />
          </Button>
        </div>

        {[items[1], items[2], items[3]].map((s, i) => (
          <div key={i} className="md:col-span-2 rounded-3xl bg-card border border-border/60 p-7 hover:shadow-lg transition-shadow" data-testid={`service-bento-${i + 1}`}>
            <span className="h-12 w-12 rounded-xl bg-accent text-accent-foreground grid place-items-center mb-5"><s.icon className="h-6 w-6" /></span>
            <h3 className="font-display font-bold text-xl mb-2">{s.title}</h3>
            <p className="text-sm text-muted-foreground leading-relaxed">{s.desc}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
