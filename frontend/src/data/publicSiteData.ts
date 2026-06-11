import type { ScheduleDay } from '@/types/schedule'

// ---------------------------------------------------------------------------
// Mock schedule — matches GET /api/schedule response shape exactly.
// Used as fallback when the API is unavailable.
// ---------------------------------------------------------------------------

export const MOCK_SCHEDULE: ScheduleDay[] = [
  {
    day: 'monday',
    day_label: 'Lunes',
    class_type: { slug: 'tren-superior', name: 'Calistenia — Tren Superior' },
    slots: ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'],
  },
  {
    day: 'tuesday',
    day_label: 'Martes',
    class_type: { slug: 'tren-inferior', name: 'Calistenia — Tren Inferior' },
    slots: ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'],
  },
  {
    day: 'wednesday',
    day_label: 'Miercoles',
    class_type: { slug: 'tren-superior', name: 'Calistenia — Tren Superior' },
    slots: ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'],
  },
  {
    day: 'thursday',
    day_label: 'Jueves',
    class_type: { slug: 'full-body', name: 'Calistenia — Full Body' },
    slots: ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'],
  },
  {
    day: 'friday',
    day_label: 'Viernes',
    class_type: { slug: 'gap', name: 'GAP + Entrenamiento Libre' },
    slots: ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'],
  },
]

// ---------------------------------------------------------------------------
// Membership plans — static for MVP (prices are business-defined, not configurable)
// ---------------------------------------------------------------------------

export interface MembershipPlan {
  id: string
  name: string
  price: number
  frequency: string
  classesPerMonth: number
  daysPerWeek: string
  benefits: string[]
  highlighted: boolean
}

export const MEMBERSHIP_PLANS: MembershipPlan[] = [
  {
    id: '2-dias',
    name: 'Plan 2 Dias',
    price: 35,
    frequency: 'mes',
    classesPerMonth: 8,
    daysPerWeek: '2 dias/semana',
    benefits: ['8 clases al mes', 'Vestuarios y duchas'],
    highlighted: false,
  },
  {
    id: '3-dias',
    name: 'Plan 3 Dias',
    price: 38,
    frequency: 'mes',
    classesPerMonth: 12,
    daysPerWeek: '3 dias/semana',
    benefits: ['12 clases al mes', 'Vestuarios y duchas', 'Asesoramiento personalizado'],
    highlighted: false,
  },
  {
    id: '4-5-dias',
    name: 'Plan 4-5 Dias',
    price: 40,
    frequency: 'mes',
    classesPerMonth: 25,
    daysPerWeek: 'Acceso ilimitado',
    benefits: [
      '20-25 clases al mes',
      'Acceso ilimitado',
      'Vestuarios y duchas',
      'Plan de entrenamiento',
    ],
    highlighted: true,
  },
]

// ---------------------------------------------------------------------------
// Class types — static descriptions for the public site
// ---------------------------------------------------------------------------

export interface ClassTypeDisplay {
  slug: string
  name: string
  description: string
  category: string
}

export const CLASS_TYPES: ClassTypeDisplay[] = [
  {
    slug: 'tren-superior',
    name: 'Tren Superior',
    description:
      'Trabaja espalda, pecho, hombros y brazos con movimientos de calistenia. Ideal para ganar fuerza y control en el tren superior.',
    category: 'Calistenia',
  },
  {
    slug: 'tren-inferior',
    name: 'Tren Inferior',
    description:
      'Sesion enfocada en piernas y gluteos con ejercicios funcionales y de fuerza. Construye una base solida.',
    category: 'Calistenia',
  },
  {
    slug: 'full-body',
    name: 'Full Body',
    description:
      'Entrenamiento completo que trabaja todos los grupos musculares en una sola sesion. Alta intensidad y funcionalidad.',
    category: 'Calistenia',
  },
  {
    slug: 'gap',
    name: 'GAP',
    description:
      'Gluteos, abdomen y piernas. Sesion de acondicionamiento especifica para tonificar y fortalecer el core y el tren inferior.',
    category: 'Acondicionamiento',
  },
  {
    slug: 'entrenamiento-libre',
    name: 'Entrenamiento Libre',
    description:
      'Sesion abierta para entrenamiento autonomo con supervision del entrenador. Trabaja a tu ritmo con los equipos del gym.',
    category: 'Libre',
  },
]

// ---------------------------------------------------------------------------
// Gym contact information
// ---------------------------------------------------------------------------

export const GYM_CONTACT = {
  address: 'C. Agustina de Aragon, 26 — 41720 Los Palacios y Villafranca, Sevilla',
  email: 'info@valhallagym.com',
  // TODO: confirm real phone number with Jose David
  phone: '+34 91 234 5678',
  instagram: 'https://www.instagram.com/itsvallhallaworkout',
  instagramHandle: '@itsvallhallaworkout',
  mapsEmbedUrl:
    'https://www.google.com/maps?q=C.+Agustina+de+Aragon+26+Los+Palacios+y+Villafranca+Sevilla&output=embed',
  hours: [
    { days: 'Lunes — Viernes', open: '06:00', close: '23:00' },
    { days: 'Sabado',          open: '08:00', close: '22:00' },
    { days: 'Domingo',         open: '08:00', close: '20:00' },
  ],
}
