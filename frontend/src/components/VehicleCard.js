import React from "react";
import { Link } from "react-router-dom";
import { Gauge, Fuel, Settings2 } from "lucide-react";
import { useLang } from "../context/LanguageContext";

export const formatPrice = (n) =>
  new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR", maximumFractionDigits: 0 }).format(n);

export const VehicleCard = ({ v, index = 0 }) => {
  const { t } = useLang();
  return (
    <Link
      to={`/vehicle/${v.id}`}
      data-testid={`vehicle-card-${v.id}`}
      className="group block rounded-2xl bg-card border border-border/60 overflow-hidden hover:shadow-xl hover:-translate-y-1.5 transition-[transform,box-shadow] duration-300 fade-up"
      style={{ animationDelay: `${index * 60}ms` }}
    >
      <div className="relative aspect-[4/3] overflow-hidden bg-muted">
        <img
          src={v.images?.[0]}
          alt={`${v.make} ${v.model}`}
          className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
        />
        {v.featured && !v.sold && (
          <span className="absolute top-3 left-3 text-xs font-semibold px-3 py-1 rounded-full bg-primary text-primary-foreground">
            {t("inventory.featured")}
          </span>
        )}
        {v.sold && (
          <span className="absolute top-3 left-3 text-xs font-semibold px-3 py-1 rounded-full bg-destructive text-destructive-foreground">
            {t("inventory.sold")}
          </span>
        )}
      </div>
      <div className="p-5">
        <div className="flex items-start justify-between gap-3">
          <div>
            <h3 className="font-display font-bold text-lg leading-tight">{v.make} {v.model}</h3>
            <p className="text-sm text-muted-foreground">{v.year}</p>
          </div>
          <p className="font-display font-extrabold text-lg text-primary whitespace-nowrap">{formatPrice(v.price)}</p>
        </div>
        <div className="flex items-center gap-4 mt-4 pt-4 border-t border-border/60 text-sm text-muted-foreground">
          <span className="flex items-center gap-1.5"><Gauge className="h-4 w-4" /> {v.mileage.toLocaleString("nl-NL")} {t("inventory.km")}</span>
          <span className="flex items-center gap-1.5"><Fuel className="h-4 w-4" /> {v.fuel}</span>
          <span className="flex items-center gap-1.5"><Settings2 className="h-4 w-4" /> {v.transmission}</span>
        </div>
      </div>
    </Link>
  );
};
