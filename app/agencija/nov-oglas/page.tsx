import ListingForm from "@/components/ListingForm";

export const metadata = {
  title: "Нов оглас — lastminuteponuda.mk",
};

export default function NewListingPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <header className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">
          Поставете нов last minute оглас
        </h1>
        <p className="mt-1 text-slate-600">
          Внесете ги основните податоци за понудата. Целата постапка трае помалку
          од минута.
        </p>
      </header>
      <ListingForm />
    </div>
  );
}
