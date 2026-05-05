"use client";

import { useActionState, useState } from "react";
import {
  createListingAction,
  type CreateListingState,
} from "@/lib/actions";
import { BOARD_LABELS, TRANSPORT_LABELS } from "@/lib/types";

const COMMON_FEATURES = [
  "Базен",
  "Плажа на 5 мин",
  "Wi-Fi",
  "Климатизација",
  "Анимација за деца",
  "Спа центар",
  "Паркинг",
  "Превоз од аеродром",
];

const initialState: CreateListingState = { status: "idle" };

export default function ListingForm() {
  const [state, action, pending] = useActionState(
    createListingAction,
    initialState
  );
  const [features, setFeatures] = useState<string[]>([]);
  const [customFeature, setCustomFeature] = useState("");

  function toggle(feature: string) {
    setFeatures((prev) =>
      prev.includes(feature)
        ? prev.filter((f) => f !== feature)
        : [...prev, feature]
    );
  }

  function addCustom() {
    const v = customFeature.trim();
    if (!v) return;
    if (!features.includes(v)) setFeatures([...features, v]);
    setCustomFeature("");
  }

  return (
    <form action={action} className="space-y-8">
      <input type="hidden" name="features" value={features.join(",")} />

      <Section title="Агенција">
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Име на агенција" required>
            <input
              className="input"
              name="agencyName"
              required
              placeholder="пр. Балкан Травел"
            />
          </Field>
          <Field label="Контакт (телефон или e-mail)" required>
            <input
              className="input"
              name="agencyContact"
              required
              placeholder="пр. +389 70 123 456"
            />
          </Field>
        </div>
      </Section>

      <Section title="Понуда">
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Наслов на оглас" required className="md:col-span-2">
            <input
              className="input"
              name="title"
              required
              placeholder="пр. 7 ноќи Анталија — All Inclusive"
            />
          </Field>
          <Field label="Дестинација (град/регион)" required>
            <input
              className="input"
              name="destination"
              required
              placeholder="пр. Анталија"
            />
          </Field>
          <Field label="Држава" required>
            <input
              className="input"
              name="country"
              required
              placeholder="пр. Турција"
            />
          </Field>
          <Field label="Хотел" required>
            <input
              className="input"
              name="hotelName"
              required
              placeholder="пр. Royal Seginus"
            />
          </Field>
          <Field label="Категорија (ѕвезди)" required>
            <select className="input" name="hotelStars" defaultValue="4" required>
              {[1, 2, 3, 4, 5].map((n) => (
                <option key={n} value={n}>
                  {n} ★
                </option>
              ))}
            </select>
          </Field>
          <Field label="Тип на пансион" required>
            <select className="input" name="boardType" required defaultValue="allInclusive">
              {Object.entries(BOARD_LABELS).map(([k, v]) => (
                <option key={k} value={k}>
                  {v}
                </option>
              ))}
            </select>
          </Field>
          <Field label="Превоз" required>
            <select className="input" name="transport" required defaultValue="plane">
              {Object.entries(TRANSPORT_LABELS).map(([k, v]) => (
                <option key={k} value={k}>
                  {v}
                </option>
              ))}
            </select>
          </Field>
        </div>
      </Section>

      <Section title="Термин и цена">
        <div className="grid gap-4 md:grid-cols-3">
          <Field label="Поаѓање" required>
            <input className="input" type="date" name="departureDate" required />
          </Field>
          <Field label="Враќање" required>
            <input className="input" type="date" name="returnDate" required />
          </Field>
          <Field label="Ноќевања" required>
            <input
              className="input"
              type="number"
              min={1}
              name="nights"
              defaultValue={7}
              required
            />
          </Field>
          <Field label="Цена по лице" required>
            <input
              className="input"
              type="number"
              min={1}
              name="pricePerPerson"
              required
              placeholder="пр. 499"
            />
          </Field>
          <Field label="Валута" required>
            <select className="input" name="currency" defaultValue="EUR" required>
              <option value="EUR">EUR</option>
              <option value="MKD">MKD</option>
              <option value="USD">USD</option>
            </select>
          </Field>
          <Field label="Слободни места" required>
            <input
              className="input"
              type="number"
              min={1}
              name="availableSeats"
              defaultValue={2}
              required
            />
          </Field>
        </div>
      </Section>

      <Section title="Карактеристики">
        <p className="text-sm text-slate-500 mb-3">
          Изберете ги карактеристиките кои важат за оваа понуда.
        </p>
        <div className="flex flex-wrap gap-2">
          {COMMON_FEATURES.map((f) => {
            const active = features.includes(f);
            return (
              <button
                key={f}
                type="button"
                onClick={() => toggle(f)}
                className={
                  active
                    ? "rounded-full border border-brand-600 bg-brand-600 px-3 py-1.5 text-sm text-white"
                    : "rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-brand-500"
                }
              >
                {active ? "✓ " : ""}
                {f}
              </button>
            );
          })}
        </div>
        <div className="mt-3 flex gap-2">
          <input
            className="input"
            placeholder="Додај друга карактеристика…"
            value={customFeature}
            onChange={(e) => setCustomFeature(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                e.preventDefault();
                addCustom();
              }
            }}
          />
          <button type="button" className="btn-secondary" onClick={addCustom}>
            Додај
          </button>
        </div>
        {features.length > 0 && (
          <div className="mt-3 flex flex-wrap gap-2">
            {features.map((f) => (
              <span key={f} className="badge">
                {f}
                <button
                  type="button"
                  onClick={() => toggle(f)}
                  className="ml-1.5 text-brand-700 hover:text-brand-900"
                  aria-label={`Отстрани ${f}`}
                >
                  ×
                </button>
              </span>
            ))}
          </div>
        )}
      </Section>

      <Section title="Опис и слика">
        <Field label="Опис" required>
          <textarea
            className="input min-h-[120px]"
            name="description"
            required
            placeholder="Накратко за понудата, локацијата, услугата…"
          />
        </Field>
        <Field label="URL на слика (опционално)">
          <input
            className="input"
            type="url"
            name="imageUrl"
            placeholder="https://…"
          />
        </Field>
      </Section>

      {state.status === "error" && (
        <div className="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">
          {state.message}
        </div>
      )}

      <div className="flex items-center justify-end gap-3">
        <button type="reset" className="btn-secondary">
          Исчисти
        </button>
        <button type="submit" className="btn-primary" disabled={pending}>
          {pending ? "Се зачувува…" : "Објави оглас"}
        </button>
      </div>
    </form>
  );
}

function Section({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="mb-4 text-lg font-semibold text-slate-900">{title}</h2>
      {children}
    </section>
  );
}

function Field({
  label,
  required,
  className,
  children,
}: {
  label: string;
  required?: boolean;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <div className={className}>
      <label className="label">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      {children}
    </div>
  );
}
