import { MEMBERSHIP_PLANS } from '@/data/publicSiteData'

export function PricingSection() {
  return (
    <section
      id="planes"
      className="py-20 px-4 sm:px-6 lg:px-8 bg-[#0f172a]"
    >
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-3">
            Planes de Membresia
          </h2>
          <p className="text-slate-400 text-base">
            Elige el plan que mejor se adapte a tu rutina de entrenamiento.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
          {MEMBERSHIP_PLANS.map((plan) => (
            <div key={plan.id} className="relative flex flex-col">
              {/* "Mas popular" badge */}
              {plan.highlighted && (
                <div className="text-center mb-2">
                  <span className="inline-block bg-[#2563eb] text-white text-xs font-bold px-3 py-1 rounded-full">
                    Mas popular
                  </span>
                </div>
              )}

              <div
                className={[
                  'bg-slate-800 rounded-2xl p-8 flex flex-col gap-6 border',
                  plan.highlighted
                    ? 'ring-2 ring-[#2563eb] border-[#2563eb]/40'
                    : 'border-slate-700',
                ].join(' ')}
              >
                {/* Plan name */}
                <div>
                  <h3 className="text-white font-bold text-xl mb-1">{plan.name}</h3>
                  <p className="text-slate-400 text-sm">{plan.daysPerWeek}</p>
                </div>

                {/* Price */}
                <div className="flex items-end gap-1">
                  <span className="text-5xl font-bold text-white">€{plan.price}</span>
                  <span className="text-slate-400 text-base mb-1">/{plan.frequency}</span>
                </div>

                {/* Benefits */}
                <ul className="space-y-2 flex-1">
                  {plan.benefits.map((benefit) => (
                    <li key={benefit} className="flex items-center gap-2 text-slate-300 text-sm">
                      <svg
                        className="w-4 h-4 text-[#60a5fa] flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2.5}
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                      {benefit}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          ))}
        </div>

        {/* Cash payment footnote */}
        <p className="text-center text-slate-500 text-sm mt-8">
          Pago en efectivo en el gimnasio
        </p>
      </div>
    </section>
  )
}
