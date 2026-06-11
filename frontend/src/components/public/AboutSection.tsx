export function AboutSection() {
  return (
    <section
      id="sobre-nosotros"
      className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900"
    >
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          {/* Text column */}
          <div>
            <h2 className="text-3xl sm:text-4xl font-bold text-white mb-6">
              Quienes somos
            </h2>
            <p className="text-slate-300 text-base leading-relaxed mb-4">
              Valhalla Gym es un gimnasio especializado en calistenia y fuerza funcional,
              ubicado en Los Palacios y Villafranca, Sevilla.
            </p>
            <p className="text-slate-300 text-base leading-relaxed mb-4">
              Nuestro metodo se basa en el entrenamiento progresivo: empezamos desde tu nivel
              actual y construimos fuerza real con el peso de tu propio cuerpo. Aqui no solo
              entrenas — formas parte de una comunidad comprometida con la constancia y la mejora.
            </p>
            <p className="text-slate-300 text-base leading-relaxed">
              Clases dirigidas de lunes a viernes, con horarios adaptados a tu dia a dia.
              Maxima intensidad. Minima excusa.
            </p>
          </div>

          {/* Decorative accent column */}
          <div className="flex items-center justify-center">
            <div className="relative w-full max-w-sm">
              <div className="bg-slate-800 rounded-2xl p-8 border border-slate-700">
                <div className="text-center space-y-6">
                  <div className="border-b border-slate-700 pb-4">
                    <p className="text-4xl font-bold text-[#60a5fa]">5</p>
                    <p className="text-slate-400 text-sm mt-1">tipos de clase</p>
                  </div>
                  <div className="border-b border-slate-700 pb-4">
                    <p className="text-4xl font-bold text-[#60a5fa]">7</p>
                    <p className="text-slate-400 text-sm mt-1">horarios al dia</p>
                  </div>
                  <div>
                    <p className="text-4xl font-bold text-[#60a5fa]">5</p>
                    <p className="text-slate-400 text-sm mt-1">dias a la semana</p>
                  </div>
                </div>
              </div>
              {/* Blue accent corner glow */}
              <div className="absolute -top-2 -right-2 w-16 h-16 bg-[#2563eb]/20 rounded-full blur-xl" />
              <div className="absolute -bottom-2 -left-2 w-12 h-12 bg-[#60a5fa]/20 rounded-full blur-xl" />
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
