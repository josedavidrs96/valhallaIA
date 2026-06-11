export function HeroSection() {
  return (
    <section
      id="inicio"
      className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 relative overflow-hidden"
    >
      {/* Subtle blue glow overlay */}
      <div className="absolute inset-0 bg-[#2563eb]/5 pointer-events-none" />

      <div className="relative z-10 text-center px-4 max-w-3xl mx-auto">
        <h1 className="text-5xl sm:text-6xl lg:text-7xl font-bold text-white tracking-wider mb-4">
          VALHALLA GYM
        </h1>
        <p className="text-lg sm:text-xl text-[#60a5fa] mb-10 font-medium">
          Donde los guerreros se forjan
        </p>
        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a
            href="#planes"
            className="bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-semibold px-8 py-3 rounded-lg transition-colors text-base w-full sm:w-auto text-center"
          >
            Unete
          </a>
          <a
            href="#horario"
            className="border border-slate-500 hover:border-slate-300 text-slate-300 hover:text-white font-semibold px-8 py-3 rounded-lg transition-colors text-base w-full sm:w-auto text-center"
          >
            Ver horario
          </a>
        </div>
      </div>
    </section>
  )
}
