import React, { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { Menu, X, Palette, Globe, Car, Check } from "lucide-react";
import { useTheme } from "../context/ThemeContext";
import { useLang } from "../context/LanguageContext";
import { Popover, PopoverContent, PopoverTrigger } from "./ui/popover";
import { Button } from "./ui/button";

const LANGS = [
  { code: "nl", label: "NL" },
  { code: "en", label: "EN" },
  { code: "tr", label: "TR" },
];

export const Header = () => {
  const { theme, setTheme, themes } = useTheme();
  const { lang, setLang, t } = useLang();
  const [open, setOpen] = useState(false);
  const loc = useLocation();
  const navigate = useNavigate();

  const links = [
    { to: "/", label: t("nav.home") },
    { to: "/inventory", label: t("nav.inventory") },
    { to: "/services", label: t("nav.services") },
    { to: "/booking", label: t("nav.booking") },
    { to: "/contact", label: t("nav.contact") },
  ];

  return (
    <header className="sticky top-0 z-50 glass border-b border-border/60" data-testid="main-header">
      <div className="max-w-7xl mx-auto px-5 sm:px-8 h-[72px] flex items-center justify-between gap-4">
        <Link to="/" className="flex items-center gap-2.5 shrink-0" data-testid="logo-link">
          <span className="h-9 w-9 rounded-xl bg-primary text-primary-foreground grid place-items-center">
            <Car className="h-5 w-5" strokeWidth={2.4} />
          </span>
          <span className="font-display text-xl font-extrabold tracking-tighter">AutoGarage</span>
        </Link>

        <nav className="hidden lg:flex items-center gap-1">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              data-testid={`nav-${l.label}`}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                loc.pathname === l.to ? "bg-accent text-accent-foreground" : "text-foreground/70 hover:text-foreground hover:bg-muted"
              }`}
            >
              {l.label}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-2">
          {/* language */}
          <Popover>
            <PopoverTrigger asChild>
              <Button variant="ghost" size="sm" className="rounded-full gap-1.5 px-3" data-testid="lang-switcher">
                <Globe className="h-4 w-4" /> <span className="text-sm font-semibold uppercase">{lang}</span>
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-32 p-1.5 bg-popover" align="end">
              {LANGS.map((l) => (
                <button
                  key={l.code}
                  onClick={() => setLang(l.code)}
                  data-testid={`lang-${l.code}`}
                  className="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-muted"
                >
                  {l.label} {lang === l.code && <Check className="h-4 w-4 text-primary" />}
                </button>
              ))}
            </PopoverContent>
          </Popover>

          {/* theme */}
          <Popover>
            <PopoverTrigger asChild>
              <Button variant="ghost" size="icon" className="rounded-full" data-testid="theme-switcher">
                <Palette className="h-5 w-5" />
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-56 p-3 bg-popover" align="end">
              <p className="text-xs uppercase tracking-wider text-muted-foreground mb-3 font-semibold">Thema</p>
              <div className="grid grid-cols-5 gap-2.5">
                {themes.map((th) => (
                  <button
                    key={th.class}
                    onClick={() => setTheme(th.class)}
                    title={th.name}
                    data-testid={`theme-${th.name}`}
                    className={`h-8 w-8 rounded-full transition-transform hover:scale-110 grid place-items-center ${
                      theme === th.class ? "ring-2 ring-offset-2 ring-foreground" : ""
                    }`}
                    style={{ backgroundColor: th.color }}
                  >
                    {theme === th.class && <Check className="h-4 w-4 text-white" />}
                  </button>
                ))}
              </div>
            </PopoverContent>
          </Popover>

          <Button
            onClick={() => navigate("/booking")}
            className="rounded-full hidden sm:inline-flex hover:scale-[1.03] transition-transform"
            data-testid="header-book-btn"
          >
            {t("nav.booking")}
          </Button>

          <button className="lg:hidden p-2" onClick={() => setOpen(!open)} data-testid="mobile-menu-toggle">
            {open ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>
        </div>
      </div>

      {open && (
        <div className="lg:hidden border-t border-border bg-background px-5 py-4 space-y-1" data-testid="mobile-menu">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              onClick={() => setOpen(false)}
              className="block px-4 py-3 rounded-lg text-sm font-medium hover:bg-muted"
            >
              {l.label}
            </Link>
          ))}
        </div>
      )}
    </header>
  );
};
