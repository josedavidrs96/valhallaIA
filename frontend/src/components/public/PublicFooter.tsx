import { GYM_CONTACT } from '@/data/publicSiteData'

export function PublicFooter() {
  return (
    <footer className="bg-slate-900 border-t border-slate-800">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
          <p className="text-slate-500 text-sm text-center sm:text-left">
            &copy; 2026 Valhalla Gym — Los Palacios y Villafranca
          </p>
          <a
            href={GYM_CONTACT.instagram}
            target="_blank"
            rel="noopener noreferrer"
            className="text-slate-400 hover:text-[#60a5fa] text-sm transition-colors"
          >
            {GYM_CONTACT.instagramHandle} ↗
          </a>
        </div>
      </div>
    </footer>
  )
}
