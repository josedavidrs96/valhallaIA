import { Link } from 'react-router-dom'

interface PublicMobileMenuProps {
  isOpen: boolean
  onClose: () => void
}

const NAV_LINKS = [
  { href: '#sobre-nosotros', label: 'Sobre nosotros' },
  { href: '#clases',         label: 'Clases'          },
  { href: '#horario',        label: 'Horario'          },
  { href: '#planes',         label: 'Planes'           },
  { href: '#contacto',       label: 'Contacto'         },
]

export function PublicMobileMenu({ isOpen, onClose }: PublicMobileMenuProps) {
  if (!isOpen) return null

  return (
    <div className="md:hidden bg-[#0f172a] border-t border-slate-800 px-4 py-4 space-y-2">
      {NAV_LINKS.map((link) => (
        <a
          key={link.href}
          href={link.href}
          onClick={onClose}
          className="block text-slate-300 hover:text-white py-2 px-3 rounded-lg hover:bg-slate-800 transition-colors text-sm font-medium"
        >
          {link.label}
        </a>
      ))}
      <div className="pt-2">
        <Link
          to="/login"
          onClick={onClose}
          className="block text-center bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm"
        >
          Iniciar sesion
        </Link>
      </div>
    </div>
  )
}
