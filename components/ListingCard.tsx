import Link from "next/link";
import { BOARD_LABELS, TRANSPORT_LABELS, type Listing } from "@/lib/types";

const dateFmt = new Intl.DateTimeFormat("mk-MK", {
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
});

function formatPrice(amount: number, currency: Listing["currency"]) {
  try {
    return new Intl.NumberFormat("mk-MK", {
      style: "currency",
      currency,
      maximumFractionDigits: 0,
    }).format(amount);
  } catch {
    return `${amount} ${currency}`;
  }
}

export default function ListingCard({ listing }: { listing: Listing }) {
  return (
    <Link
      href={`/oglasi/${listing.id}`}
      className="block rounded-lg border border-slate-200 bg-white shadow-sm hover:shadow-md hover:border-brand-300 transition overflow-hidden"
    >
      {listing.imageUrl ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={listing.imageUrl}
          alt={listing.title}
          className="h-44 w-full object-cover"
        />
      ) : (
        <div className="h-44 w-full bg-gradient-to-br from-brand-100 to-brand-50 flex items-center justify-center text-brand-700 font-semibold">
          {listing.destination}
        </div>
      )}
      <div className="p-4">
        <div className="flex items-start justify-between gap-3">
          <h3 className="text-base font-semibold text-slate-900 line-clamp-2">
            {listing.title}
          </h3>
          <span className="shrink-0 text-amber-500 text-sm">
            {"★".repeat(listing.hotelStars)}
          </span>
        </div>
        <p className="mt-1 text-sm text-slate-600">
          {listing.destination}, {listing.country} · {listing.hotelName}
        </p>
        <div className="mt-3 flex flex-wrap gap-1.5">
          <span className="badge">{BOARD_LABELS[listing.boardType]}</span>
          <span className="badge">{TRANSPORT_LABELS[listing.transport]}</span>
          <span className="badge">{listing.nights} ноќи</span>
        </div>
        <div className="mt-4 flex items-end justify-between">
          <div className="text-xs text-slate-500">
            {dateFmt.format(new Date(listing.departureDate))} —{" "}
            {dateFmt.format(new Date(listing.returnDate))}
          </div>
          <div className="text-right">
            <div className="text-lg font-bold text-brand-700">
              {formatPrice(listing.pricePerPerson, listing.currency)}
            </div>
            <div className="text-xs text-slate-500">по лице</div>
          </div>
        </div>
      </div>
    </Link>
  );
}
