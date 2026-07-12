import React, { useState } from "react";
import { useNavigate, Navigate } from "react-router-dom";
import { Car, Lock } from "lucide-react";
import { useLang } from "../context/LanguageContext";
import { useAuth } from "../context/AuthContext";
import { formatApiError } from "../lib/api";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Button } from "../components/ui/button";

export default function Login() {
  const { t } = useLang();
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  if (user) return <Navigate to="/admin" replace />;

  const submit = async (e) => {
    e.preventDefault();
    setError(""); setLoading(true);
    try {
      await login(email, password);
      navigate("/admin");
    } catch (err) {
      setError(formatApiError(err.response?.data?.detail) || err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] grid place-items-center px-5 py-14" data-testid="login-page">
      <div className="w-full max-w-md rounded-2xl bg-card border border-border/60 p-8 shadow-lg">
        <div className="text-center mb-8">
          <span className="h-12 w-12 rounded-xl bg-primary text-primary-foreground grid place-items-center mx-auto mb-4"><Car className="h-6 w-6" /></span>
          <h1 className="font-display text-2xl font-extrabold tracking-tight">{t("admin.title")}</h1>
        </div>
        <form onSubmit={submit} className="space-y-5">
          <div>
            <Label className="text-xs uppercase tracking-wider text-muted-foreground mb-1.5 block">{t("admin.email")}</Label>
            <Input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} className="rounded-xl" data-testid="login-email" />
          </div>
          <div>
            <Label className="text-xs uppercase tracking-wider text-muted-foreground mb-1.5 block">{t("admin.password")}</Label>
            <Input required type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="rounded-xl" data-testid="login-password" />
          </div>
          {error && <p className="text-sm text-destructive" data-testid="login-error">{error}</p>}
          <Button type="submit" disabled={loading} className="rounded-full w-full h-11 gap-2" data-testid="login-submit">
            <Lock className="h-4 w-4" /> {loading ? t("common.loading") : t("admin.loginBtn")}
          </Button>
        </form>
      </div>
    </div>
  );
}
