import { GYM_CONTACT } from '@/data/publicSiteData'

export function ContactSection() {
  return (
    <section
      id="contacto"
      className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900"
    >
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-3">
            Contacto
          </h2>
          <p className="text-slate-400 text-base">
            Encuéntranos en Los Palacios y Villafranca, Sevilla.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
          {/* Left column — contact info */}
          <div className="space-y-6">
            {/* Address */}
            <div className="flex items-start gap-3">
              <svg
                className="w-5 h-5 text-[#60a5fa] flex-shrink-0 mt-0.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <p className="text-slate-300 text-sm">{GYM_CONTACT.address}</p>
            </div>

            {/* Phone */}
            <div className="flex items-center gap-3">
              <svg
                className="w-5 h-5 text-[#60a5fa] flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
              </svg>
              <span className="text-slate-300 text-sm">{GYM_CONTACT.phone}</span>
            </div>

            {/* Email */}
            <div className="flex items-center gap-3">
              <svg
                className="w-5 h-5 text-[#60a5fa] flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              <a
                href={`mailto:${GYM_CONTACT.email}`}
                className="text-slate-300 hover:text-[#60a5fa] text-sm transition-colors"
              >
                {GYM_CONTACT.email}
              </a>
            </div>

            {/* Opening hours */}
            <div>
              <h3 className="text-white font-semibold text-sm mb-3">Horario de apertura</h3>
              <table className="w-full">
                <tbody>
                  {GYM_CONTACT.hours.map((row) => (
                    <tr key={row.days} className="border-b border-slate-800 last:border-0">
                      <td className="text-slate-400 text-sm py-2 pr-4">{row.days}</td>
                      <td className="text-slate-300 text-sm py-2 font-mono">
                        {row.open} — {row.close}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Instagram */}
            <div>
              <a
                href={GYM_CONTACT.instagram}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-[#60a5fa] hover:text-white text-sm transition-colors"
              >
                <svg
                  className="w-4 h-4"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
                {GYM_CONTACT.instagramHandle}
              </a>
            </div>
          </div>

          {/* Right column — Google Maps embed */}
          <div className="w-full h-64 md:h-full min-h-64 rounded-xl overflow-hidden border border-slate-700">
            <iframe
              src={GYM_CONTACT.mapsEmbedUrl}
              width="100%"
              height="100%"
              style={{ border: 0, minHeight: '16rem' }}
              allowFullScreen
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="Ubicacion Valhalla Gym"
            />
          </div>
        </div>
      </div>
    </section>
  )
}
