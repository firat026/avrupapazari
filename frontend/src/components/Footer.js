import React from "react";
import { Link } from "react-router-dom";
import { Car, MapPin, Phone, Mail, Clock } from "lucide-react";
import { useLang } from "../context/LanguageContext";

export const Footer = () => {
  const { t } = useLang();
  return (
    <footer className="bg-foreground text-background mt-24" data-testid="main-footer">
      <div className="max-w-7xl mx-auto px-5 sm:px-8 py-16 grid gap-10 md:grid-cols-4">
        <div className="md:col-span-2 max-w-sm">
          <div className="flex items-center gap-2.5 mb-4">
            <span className="h-9 w-9 rounded-xl bg-primary text-primary-foreground grid place-items-center">
              <Car className="h-5 w-5" strokeWidth={2.4} />
            </span>
            <span className="font-display text-xl font-extrabold tracking-tighter">AutoGarage</span>
          </div>
          <p className="text-background/60 leading-relaxed">{t("footer.tagline")}</p>
        </div>

        <div>
          <h4 className="font-display font-bold mb-4 text-background">{t("footer.quick")}</h4>
          <ul className="space-y-2.5 text-background/60">
            <li><Link to="/inventory" className="hover:text-primary transition-colors">{t("nav.inventory")}</Link></li>
            <li><Link to="/services" className="hover:text-primary transition-colors">{t("nav.services")}</Link></li>
            <li><Link to="/booking" className="hover:text-primary transition-colors">{t("nav.booking")}</Link></li>
            <li><Link to="/contact" className="hover:text-primary transition-colors">{t("nav.contact")}</Link></li>
          </ul>
        </div>

        <div>
          <h4 className="font-display font-bold mb-4 text-background">{t("footer.contact")}</h4>
          <ul className="space-y-3 text-background/60 text-sm">
            <li className="flex gap-2.5"><MapPin className="h-4 w-4 mt-0.5 shrink-0 text-primary" /> Autoweg 24, 1012 AB Amsterdam</li>
            <li className="flex gap-2.5"><Phone className="h-4 w-4 mt-0.5 shrink-0 text-primary" /> +31 20 123 4567</li>
            <li className="flex gap-2.5"><Mail className="h-4 w-4 mt-0.5 shrink-0 text-primary" /> info@autogarage.nl</li>
            <li className="flex gap-2.5"><Clock className="h-4 w-4 mt-0.5 shrink-0 text-primary" /> {t("contact.hoursValue")}</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-background/10 py-6 text-center text-background/40 text-sm">
        © {new Date().getFullYear()} AutoGarage. {t("footer.rights")}
      </div>
    </footer>
  );
};
