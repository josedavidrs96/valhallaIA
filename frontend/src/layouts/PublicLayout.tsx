import { PublicHeader } from '@/components/public/PublicHeader'
import { PublicFooter } from '@/components/public/PublicFooter'

interface PublicLayoutProps {
  children: React.ReactNode
}

export function PublicLayout({ children }: PublicLayoutProps) {
  return (
    <div className="min-h-screen bg-[#0f172a] scroll-smooth">
      <PublicHeader />
      <main>{children}</main>
      <PublicFooter />
    </div>
  )
}
