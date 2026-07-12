import React, { createContext, useContext, useEffect, useState } from "react";

export const THEMES = [
  { name: "Azure", class: "theme-azure", color: "#2563eb" },
  { name: "Crimson", class: "theme-crimson", color: "#dc2543" },
  { name: "Sage", class: "theme-sage", color: "#3d8f66" },
  { name: "Champagne", class: "theme-champagne", color: "#c39a3b" },
  { name: "Onyx", class: "theme-onyx", color: "#212121" },
  { name: "Cobalt", class: "theme-cobalt", color: "#1e40d6" },
  { name: "Copper", class: "theme-copper", color: "#d1552b" },
  { name: "Titanium", class: "theme-titanium", color: "#546678" },
  { name: "Emerald", class: "theme-emerald", color: "#159768" },
  { name: "Plum", class: "theme-plum", color: "#96479a" },
];

const ThemeContext = createContext(null);

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState(() => localStorage.getItem("ag_theme") || "theme-azure");

  useEffect(() => {
    const root = document.documentElement;
    THEMES.forEach((t) => root.classList.remove(t.class));
    root.classList.add(theme);
    localStorage.setItem("ag_theme", theme);
  }, [theme]);

  return (
    <ThemeContext.Provider value={{ theme, setTheme, themes: THEMES }}>
      {children}
    </ThemeContext.Provider>
  );
}

export const useTheme = () => useContext(ThemeContext);
