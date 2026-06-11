import { CLASS_TYPES } from '@/data/publicSiteData'

export function ClassTypesSection() {
  return (
    <section
      id="clases"
      className="py-20 px-4 sm:px-6 lg:px-8 bg-[#0f172a]"
    >
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-3">
            Tipos de Clase
          </h2>
          <p className="text-slate-400 text-base max-w-xl mx-auto">
            Cada dia de la semana tiene su propio tipo de entrenamiento. Elige tu plan y
            accede a las clases que mejor se adapten a tu rutina.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {CLASS_TYPES.map((classType) => (
            <div
              key={classType.slug}
              className="bg-slate-800 rounded-xl p-6 border border-slate-700 hover:border-slate-600 transition-colors"
            >
              <div className="flex items-start justify-between mb-3">
                <h3 className="text-white font-bold text-lg">{classType.name}</h3>
                <span className="text-xs text-[#60a5fa] border border-[#2563eb]/40 bg-[#2563eb]/10 px-2 py-1 rounded-full whitespace-nowrap ml-2 flex-shrink-0">
                  {classType.category}
                </span>
              </div>
              <p className="text-slate-300 text-sm leading-relaxed">
                {classType.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
