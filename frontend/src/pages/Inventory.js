import React, { useState, useEffect, useCallback } from "react";
import { useSearchParams } from "react-router-dom";
import { X } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import api from "../lib/api";
import { VehicleCard } from "../components/VehicleCard";
import { Input } from "../components/ui/input";
import { Button } from "../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";

export default function Inventory() {
  const { t } = useLang();
  const [params, setParams] = useSearchParams();
  const [vehicles, setVehicles] = useState([]);
  const [makes, setMakes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState(params.get("search") || "");

  const f = {
    make: params.get("make") || "",
    fuel: params.get("fuel") || "",
    body_type: params.get("body_type") || "",
    max_price: params.get("max_price") || "",
  };

  const load = useCallback(() => {
    setLoading(true);
    const p = {};
    ["make", "fuel", "body_type", "max_price", "search"].forEach((k) => {
      const val = params.get(k);
      if (val) p[k] = val;
    });
    api.get("/vehicles", { params: p }).then((r) => setVehicles(r.data)).finally(() => setLoading(false));
  }, [params]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { api.get("/vehicles/makes").then((r) => setMakes(r.data)); }, []);

  const setFilter = (key, value) => {
    const next = new URLSearchParams(params);
    if (value && value !== "any") next.set(key, value);
    else next.delete(key);
    setParams(next);
  };

  const applySearch = (e) => {
    e.preventDefault();
    setFilter("search", search);
  };

  const reset = () => { setSearch(""); setParams({}); };

  return (
    <div className="max-w-7xl mx-auto px-5 sm:px-8 py-14" data-testid="inventory-page">
      <div className="mb-10">
        <h1 className="font-display text-4xl md:text-5xl font-extrabold tracking-tighter">{t("inventory.title")}</h1>
        <p className="text-foreground/70 mt-3 text-lg">{t("inventory.subtitle")}</p>
      </div>

      <div className="rounded-2xl bg-card border border-border/60 p-5 mb-10 grid gap-3 md:grid-cols-5" data-testid="inventory-filters">
        <form onSubmit={applySearch} className="md:col-span-1">
          <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t("inventory.search")} className="rounded-xl" data-testid="filter-search" />
        </form>
        <Select value={f.make || "any"} onValueChange={(v) => setFilter("make", v)}>
          <SelectTrigger className="rounded-xl" data-testid="filter-make"><SelectValue placeholder={t("inventory.make")} /></SelectTrigger>
          <SelectContent className="bg-popover">
            <SelectItem value="any">{t("inventory.make")}</SelectItem>
            {makes.map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
          </SelectContent>
        </Select>
        <Select value={f.fuel || "any"} onValueChange={(v) => setFilter("fuel", v)}>
          <SelectTrigger className="rounded-xl" data-testid="filter-fuel"><SelectValue placeholder={t("inventory.fuel")} /></SelectTrigger>
          <SelectContent className="bg-popover">
            <SelectItem value="any">{t("inventory.fuel")}</SelectItem>
            {["Benzine", "Diesel", "Elektrisch", "Hybride"].map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
          </SelectContent>
        </Select>
        <Select value={f.body_type || "any"} onValueChange={(v) => setFilter("body_type", v)}>
          <SelectTrigger className="rounded-xl" data-testid="filter-body"><SelectValue placeholder={t("inventory.body")} /></SelectTrigger>
          <SelectContent className="bg-popover">
            <SelectItem value="any">{t("inventory.body")}</SelectItem>
            {["Sedan", "Coupé", "Hatchback", "SUV", "Stationwagon"].map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
          </SelectContent>
        </Select>
        <Button variant="ghost" onClick={reset} className="rounded-xl gap-2" data-testid="filter-reset"><X className="h-4 w-4" /> {t("inventory.reset")}</Button>
      </div>

      <p className="text-sm text-muted-foreground mb-6" data-testid="results-count">{vehicles.length} {t("inventory.results")}</p>

      {loading ? (
        <p className="text-muted-foreground py-20 text-center">{t("common.loading")}</p>
      ) : vehicles.length === 0 ? (
        <p className="text-muted-foreground py-20 text-center" data-testid="no-results">{t("inventory.noResults")}</p>
      ) : (
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          {vehicles.map((v, i) => <VehicleCard key={v.id} v={v} index={i} />)}
        </div>
      )}
    </div>
  );
}
