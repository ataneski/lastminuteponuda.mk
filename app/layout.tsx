import type { Metadata } from "next";
import Link from "next/link";
import "./globals.css";

export const metadata: Metadata = {
  title: "lastminuteponuda.mk — Last minute понуди од македонски агенции",
  description:
    "Платформа за last minute туристички понуди. Агенциите брзо и лесно поставуваат огласи со цени и карактеристики.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="mk">
      <body className="min-h-screen flex flex-col">
        <header className="bg-white border-b border-slate-200">
          <div className="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
            <Link href="/" className="flex items-center gap-2">
              <span className="text-xl font-bold text-brand-700">
                lastminuteponuda
              </span>
              <span className="text-xl font-bold text-slate-400">.mk</span>
            </Link>
            <nav className="flex items-center gap-3">
              <Link
                href="/oglasi"
                className="text-sm font-medium text-slate-600 hover:text-slate-900"
              >
                Огласи
              </Link>
              <Link href="/agencija/nov-oglas" className="btn-primary">
                + Нов оглас
              </Link>
            </nav>
          </div>
        </header>
        <main className="flex-1">{children}</main>
        <footer className="bg-white border-t border-slate-200 mt-12">
          <div className="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500">
            © {new Date().getFullYear()} lastminuteponuda.mk — Last minute понуди
            за вашите патувања.
          </div>
        </footer>
      </body>
    </html>
  );
}
