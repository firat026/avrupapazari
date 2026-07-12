import React, { createContext, useContext, useState } from "react";
import { translations } from "../data/translations";

const LanguageContext = createContext(null);

export function LanguageProvider({ children }) {
  const [lang, setLang] = useState(() => localStorage.getItem("ag_lang") || "nl");

  const change = (l) => {
    setLang(l);
    localStorage.setItem("ag_lang", l);
  };

  const t = (key) => {
    const parts = key.split(".");
    let cur = translations[lang];
    for (const p of parts) {
      cur = cur?.[p];
      if (cur === undefined) {
        cur = key;
        break;
      }
    }
    return cur;
  };

  return (
    <LanguageContext.Provider value={{ lang, setLang: change, t }}>
      {children}
    </LanguageContext.Provider>
  );
}

export const useLang = () => useContext(LanguageContext);
