import { PublicLayout } from '@/layouts/PublicLayout'
import { HeroSection } from '@/components/public/HeroSection'
import { AboutSection } from '@/components/public/AboutSection'
import { ClassTypesSection } from '@/components/public/ClassTypesSection'
import { ScheduleSection } from '@/components/public/ScheduleSection'
import { PricingSection } from '@/components/public/PricingSection'
import { ContactSection } from '@/components/public/ContactSection'

export default function PublicHomePage() {
  return (
    <PublicLayout>
      <HeroSection />
      <AboutSection />
      <ClassTypesSection />
      <ScheduleSection />
      <PricingSection />
      <ContactSection />
    </PublicLayout>
  )
}
