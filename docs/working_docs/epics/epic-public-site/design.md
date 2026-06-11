# Solution Design: epic-public-site — Public Website

**Requirement:** [requirements.md](requirements.md)
**Validation:** [validation.md](validation.md)
**Date:** 2026-06-11
**Status:** Draft
**Bounded Context:** Frontend only — no new backend bounded context

---

## Summary

This epic is **100% frontend**. No new backend entities, no new migrations, no new domain
layer. The implementation creates a public-facing React page at route `/`, composed of 6
independent section components inside a `PublicLayout`, with a custom hook
`usePublicSchedule` that calls `GET /api/schedule` (falling back to static mock data if the
endpoint is unavailable).

The only existing file changed in the application is `frontend/src/router/index.tsx`, where
a new route `"/"` is added pointing to the new `PublicHomePage`.

---

## Architecture Decision

### Single-page with anchor navigation (no sub-routes)

All 6 sections live inside one `PublicHomePage` component. Navigation is done via anchor
links (`href="#section-id"`). This avoids unnecessary route complexity for what is a
marketing one-pager.

**Alternative considered:** Separate routes per section (`/horario`, `/precios`, etc.).
**Rejected because:** Adds routing complexity with no UX benefit for a single-page site.
The URL bar does not need to change when scrolling between sections.

### Frontend-first with API contract already defined

The API contract for `GET /api/schedule` is defined in the requirements document. The
backend endpoint will be delivered by epic-classes. Until then, the `usePublicSchedule`
hook uses static mock data with the exact same JSON shape, so there is zero integration
work when the real endpoint ships.

### `PublicLayout` is a layout component, not an auth wrapper

`PublicLayout` renders the header, the page content (via `children`), and the footer. It
does NOT use `useAuth()` or wrap `AuthProvider`. It relies on the fact that it renders
inside `AppRouter`, which already provides the auth context. The header shows
"Iniciar sesion" as a static link to `/login` — no conditional rendering based on auth
state in MVP.

### Static data for membership plans

Prices and plan details are hardcoded as a TypeScript constant. There is no backend
endpoint for plans in MVP. This is the correct decision because plans are not configurable
in the current system — they are defined by the business and known at build time.

---

## Existing Code Analysis

| Component | Location | Reusable | Modifications Needed |
|-----------|----------|----------|---------------------|
| `AppRouter` | `frontend/src/router/index.tsx` | Yes | Add `<Route path="/" element={<PublicHomePage />} />` before catch-all |
| `api.ts` (axios client) | `frontend/src/services/api.ts` | Yes — for schedule fetch | None — used as-is |
| Brand color tokens | Used in `LoginPage.tsx` | Reference only | Define as shared constants or use inline Tailwind classes consistently |
| `LoginPage` styling patterns | `frontend/src/pages/auth/LoginPage.tsx` | Reference for Tailwind class patterns | None |

---

## File Structure

All new files live under `frontend/src/`:

```
frontend/src/
├── pages/
│   └── public/
│       └── PublicHomePage.tsx          # Root page — composes all sections
├── layouts/
│   └── PublicLayout.tsx                # Header + Footer wrapper
├── components/
│   └── public/
│       ├── HeroSection.tsx             # Hero with tagline + CTA
│       ├── AboutSection.tsx            # Sobre nosotros
│       ├── ClassTypesSection.tsx       # 5 class type cards
│       ├── ScheduleSection.tsx         # Weekly schedule table
│       ├── PricingSection.tsx          # 3 membership plan cards
│       ├── ContactSection.tsx          # Address, hours, map, social
│       ├── PublicHeader.tsx            # Sticky nav + login button
│       ├── PublicFooter.tsx            # Copyright + social links
│       └── PublicMobileMenu.tsx        # Collapsible mobile nav (< 768px)
├── hooks/
│   └── usePublicSchedule.ts           # Fetches GET /api/schedule or returns mock
├── data/
│   └── publicSiteData.ts              # Static data: plans, class types, mock schedule
└── types/
    └── schedule.ts                    # TypeScript types for schedule API response
```

**Modified files:**
```
frontend/src/router/index.tsx           # Add route "/"
```

---

## Implementation Plan

### 1. Types (`frontend/src/types/schedule.ts`)

TypeScript interfaces matching the API contract:

```typescript
export interface ClassTypeInfo {
  slug: string
  name: string
}

export interface ScheduleDay {
  day: string           // "monday" | "tuesday" | ...
  day_label: string     // "Lunes" | "Martes" | ...
  class_type: ClassTypeInfo
  slots: string[]       // ["07:45", "12:15", ...]
}

export interface ScheduleResponse {
  schedule: ScheduleDay[]
}
```

---

### 2. Static Data (`frontend/src/data/publicSiteData.ts`)

All static content in one place. Covers:

**Mock schedule** (matches API contract shape exactly — used as fallback):
```typescript
export const MOCK_SCHEDULE: ScheduleDay[] = [
  { day: 'monday',    day_label: 'Lunes',     class_type: { slug: 'tren-superior', name: 'Calistenia — Tren Superior' }, slots: ['07:45','12:15','16:15','17:30','18:45','20:00','21:15'] },
  { day: 'tuesday',   day_label: 'Martes',    class_type: { slug: 'tren-inferior', name: 'Calistenia — Tren Inferior' }, slots: ['07:45','12:15','16:15','17:30','18:45','20:00','21:15'] },
  { day: 'wednesday', day_label: 'Miercoles', class_type: { slug: 'tren-superior', name: 'Calistenia — Tren Superior' }, slots: ['07:45','12:15','16:15','17:30','18:45','20:00','21:15'] },
  { day: 'thursday',  day_label: 'Jueves',    class_type: { slug: 'full-body',     name: 'Calistenia — Full Body'    }, slots: ['07:45','12:15','16:15','17:30','18:45','20:00','21:15'] },
  { day: 'friday',    day_label: 'Viernes',   class_type: { slug: 'gap',           name: 'GAP + Entrenamiento Libre' }, slots: ['07:45','12:15','16:15','17:30','18:45','20:00','21:15'] },
]
```

**Membership plans** (static — hardcoded for MVP):
```typescript
export interface MembershipPlan {
  id: string
  name: string              // "Plan 2 Dias"
  price: number             // 35
  frequency: string         // "mes"
  classesPerMonth: number   // 8
  daysPerWeek: string       // "2 dias/semana"
  benefits: string[]
  highlighted: boolean      // true for the 4-5 day plan
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
    benefits: ['20-25 clases al mes', 'Acceso ilimitado', 'Vestuarios y duchas', 'Plan de entrenamiento'],
    highlighted: true,
  },
]
```

**Class types** (for ClassTypesSection — static descriptions):
```typescript
export interface ClassTypeDisplay {
  slug: string
  name: string
  description: string
  category: string
}

export const CLASS_TYPES: ClassTypeDisplay[] = [
  { slug: 'tren-superior', name: 'Tren Superior', description: 'Trabaja espalda, pecho, hombros y brazos con movimientos de calistenia. Ideal para ganar fuerza y control en el tren superior.', category: 'Calistenia' },
  { slug: 'tren-inferior', name: 'Tren Inferior', description: 'Sesion enfocada en piernas y gluteos con ejercicios funcionales y de fuerza. Construye una base solida.', category: 'Calistenia' },
  { slug: 'full-body',     name: 'Full Body',     description: 'Entrenamiento completo que trabaja todos los grupos musculares en una sola sesion. Alta intensidad y funcionalidad.', category: 'Calistenia' },
  { slug: 'gap',           name: 'GAP',           description: 'Gluteos, abdomen y piernas. Sesion de acondicionamiento especifica para tonificar y fortalecer el core y el tren inferior.', category: 'Acondicionamiento' },
  { slug: 'entrenamiento-libre', name: 'Entrenamiento Libre', description: 'Sesion abierta para entrenamiento autonomo con supervision del entrenador. Trabaja a tu ritmo con los equipos del gym.', category: 'Libre' },
]
```

**Contact info** (to avoid magic strings scattered across components):
```typescript
export const GYM_CONTACT = {
  address: 'C. Agustina de Aragon, 26 — 41720 Los Palacios y Villafranca, Sevilla',
  email: 'info@valhallagym.com',
  phone: '+34 91 234 5678', // TODO: confirm real phone number with Jose David
  instagram: 'https://www.instagram.com/itsvallhallaworkout',
  instagramHandle: '@itsvallhallaworkout',
  mapsEmbedUrl: 'https://www.google.com/maps?q=C.+Agustina+de+Aragon+26+Los+Palacios+y+Villafranca+Sevilla&output=embed',
  hours: [
    { days: 'Lunes — Viernes', open: '06:00', close: '23:00' },
    { days: 'Sabado',          open: '08:00', close: '22:00' },
    { days: 'Domingo',         open: '08:00', close: '20:00' },
  ],
}
```

---

### 3. Hook: `usePublicSchedule` (`frontend/src/hooks/usePublicSchedule.ts`)

```typescript
// State shape
interface UsePublicScheduleResult {
  schedule: ScheduleDay[]
  isLoading: boolean
  isError: boolean
  isMock: boolean   // true when falling back to static data
}
```

**Behaviour:**
1. On mount: calls `GET /api/schedule` via `api.ts` (no auth header needed — the axios
   interceptor only adds the token if one exists in localStorage; no token = no header sent)
2. On success: returns the `schedule` array from the response
3. On error (network, 4xx, 5xx): sets `isError = true`, `isMock = true`, returns
   `MOCK_SCHEDULE` as `schedule` — the user sees data regardless
4. Sets `isLoading = false` when the request settles

**No dependencies beyond `api.ts` and the static data file.**

---

### 4. Layout: `PublicLayout` (`frontend/src/layouts/PublicLayout.tsx`)

```typescript
interface PublicLayoutProps {
  children: React.ReactNode
}
```

Renders:
```
<div className="min-h-screen bg-[#0f172a]">
  <PublicHeader />          // sticky, z-50
  <main>{children}</main>
  <PublicFooter />
</div>
```

**Does NOT use `useAuth()`** — The header login link is a static `<a href="/login">` or
React Router `<Link to="/login">`. No auth state needed.

---

### 5. Header: `PublicHeader` (`frontend/src/components/public/PublicHeader.tsx`)

Desktop layout (≥ 768px):
```
[VALHALLA GYM logo text]    [Sobre nosotros] [Clases] [Horario] [Planes] [Contacto]    [Iniciar sesion →]
```

Mobile layout (< 768px):
```
[VALHALLA GYM logo text]                                                  [☰ menu icon]
```
— hamburger opens `PublicMobileMenu` (full-screen overlay or slide-down)

**Sticky behaviour:** `position: sticky; top: 0; z-index: 50` via Tailwind `sticky top-0 z-50`.

Nav links: anchor links with smooth scroll. Example:
```tsx
<a href="#sobre-nosotros" className="...">Sobre nosotros</a>
```

Login button:
```tsx
<Link to="/login" className="bg-[#2563eb] hover:bg-[#1d4ed8] text-white ...">
  Iniciar sesion
</Link>
```

---

### 6. Mobile Menu: `PublicMobileMenu` (`frontend/src/components/public/PublicMobileMenu.tsx`)

```typescript
interface PublicMobileMenuProps {
  isOpen: boolean
  onClose: () => void
}
```

Controlled by `PublicHeader` state (`useState<boolean>(false)`). Renders as a full-width
dropdown or overlay below the header when `isOpen === true`. Contains the same nav links
as the desktop header plus the "Iniciar sesion" button. Closes on link click.

---

### 7. Section Components

Each section is a standalone component receiving no props (data comes from constants or
the hook). All sections use `id` attributes for anchor navigation.

#### `HeroSection` (`frontend/src/components/public/HeroSection.tsx`)

```
id="inicio"
Background: dark gradient (bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800)
  + optional subtle blue overlay
Content:
  <h1> VALHALLA GYM </h1>               // large, bold, white
  <p>  Donde los guerreros se forjan </p> // accent blue #60a5fa
  <a href="#planes">                     // CTA scrolls to pricing
    Unete
  </a>
  <a href="#horario">                    // secondary CTA
    Ver horario
  </a>
Min-height: 100vh (full viewport height)
```

#### `AboutSection` (`frontend/src/components/public/AboutSection.tsx`)

```
id="sobre-nosotros"
Two-column layout on desktop, stacked on mobile:
  Left: text (gym description, philosophy, specialization in calisthenics)
  Right: visual accent (brand color block or decorative element)
```

Content (no "n with tilde"):
- Titulo: "Quienes somos"
- Descripcion: Gimnasio especializado en calistenia y fuerza funcional en Los Palacios y Villafranca, Sevilla. Filosofia de entrenamiento progresivo, comunidad y constancia.

#### `ClassTypesSection` (`frontend/src/components/public/ClassTypesSection.tsx`)

```
id="clases"
Title: "Tipos de Clase"
Grid: 3 cols on desktop, 2 on tablet, 1 on mobile
Source: CLASS_TYPES constant (5 items)
Each card:
  - Slug-based icon/color accent
  - Class name (bold)
  - Category badge
  - Description text
```

#### `ScheduleSection` (`frontend/src/components/public/ScheduleSection.tsx`)

```
id="horario"
Title: "Horario Semanal"
Uses: usePublicSchedule() hook
States:
  - isLoading → skeleton rows or spinner
  - loaded    → schedule table
  - isError   → subtle indicator "Datos aproximados" (still shows mock data)

Table layout:
  Rows = time slots (07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15)
  Cols = days (Lunes, Martes, Miercoles, Jueves, Viernes)
  Cell = class type name for that day (same class type per column)

Mobile: horizontal scroll wrapper (overflow-x-auto)
```

Table construction logic:
```typescript
// Derive unique time slots from first day's slots (all days share the same slots)
const slots = schedule[0]?.slots ?? []
// For each slot row, iterate over schedule days to fill cells
```

#### `PricingSection` (`frontend/src/components/public/PricingSection.tsx`)

```
id="planes"
Title: "Planes de Membresia"
Source: MEMBERSHIP_PLANS constant (3 items)
Layout: 3 cards in a row on desktop, stacked on mobile

Each card:
  - Plan name
  - Price (large, prominent): €XX/mes
  - Days / access info
  - Benefits list (checkmarks)
  - Highlighted card (4-5 dias): different border color (blue-600 ring), "Mas popular" badge

No CTA button on cards (cash payment at gym, info only)
Footnote: "Pago en efectivo en el gimnasio"
```

#### `ContactSection` (`frontend/src/components/public/ContactSection.tsx`)

```
id="contacto"
Title: "Contacto"
Two columns on desktop, stacked on mobile:
  Left column:
    - Address (with location pin icon)
    - Phone (with phone icon) — TODO placeholder
    - Email (with mail icon)
    - Opening hours table (days + hours)
    - Instagram link
  Right column:
    - Google Maps iframe embed
    - src: GYM_CONTACT.mapsEmbedUrl
    - allow="fullscreen"
    - Loading="lazy"
```

---

### 8. Footer: `PublicFooter` (`frontend/src/components/public/PublicFooter.tsx`)

Simple single-row footer:
```
© 2026 Valhalla Gym — Los Palacios y Villafranca    [Instagram link]
```

Dark background matching the page. Small text in `slate-500`.

---

### 9. Page: `PublicHomePage` (`frontend/src/pages/public/PublicHomePage.tsx`)

```typescript
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
```

No state, no hooks at this level. All logic is encapsulated in the section components.

---

### 10. Router Change (`frontend/src/router/index.tsx`)

Add one route before the catch-all `path="*"`:

```tsx
// Add lazy import at top:
const PublicHomePage = lazy(() => import('@/pages/public/PublicHomePage'))

// Add route BEFORE the catch-all <Route path="*" ... />:
<Route path="/" element={<PublicHomePage />} />
```

**Why this works:** React Router v6 matches routes in order. The explicit `path="/"` will
match exactly the root URL and render `PublicHomePage`. The catch-all `path="*"` (which
triggers `DefaultRedirect`) will not fire for `/`.

**`DefaultRedirect` unchanged:** For all other unmatched paths, unauthenticated users are
still redirected to `/login` — this behaviour is preserved.

---

## Collateral Changes

### Files to Modify

| File | Change Type | Description |
|------|-------------|-------------|
| `frontend/src/router/index.tsx` | Addition | Add `lazy` import for `PublicHomePage` + `<Route path="/" element={<PublicHomePage />} />` before catch-all |

### New Files (all new — no existing files overwritten)

| File | Purpose |
|------|---------|
| `frontend/src/types/schedule.ts` | TypeScript types for schedule API |
| `frontend/src/data/publicSiteData.ts` | Static data constants |
| `frontend/src/hooks/usePublicSchedule.ts` | Schedule data fetching hook |
| `frontend/src/layouts/PublicLayout.tsx` | Header + content + footer wrapper |
| `frontend/src/components/public/PublicHeader.tsx` | Sticky nav header |
| `frontend/src/components/public/PublicMobileMenu.tsx` | Mobile hamburger nav |
| `frontend/src/components/public/PublicFooter.tsx` | Page footer |
| `frontend/src/components/public/HeroSection.tsx` | Hero with CTA |
| `frontend/src/components/public/AboutSection.tsx` | About section |
| `frontend/src/components/public/ClassTypesSection.tsx` | Class type cards |
| `frontend/src/components/public/ScheduleSection.tsx` | Weekly schedule table |
| `frontend/src/components/public/PricingSection.tsx` | Pricing cards |
| `frontend/src/components/public/ContactSection.tsx` | Contact + Maps |
| `frontend/src/pages/public/PublicHomePage.tsx` | Root public page |

### Breaking Changes

None. The only modified file (`router/index.tsx`) adds a new route — it does not change
any existing route. Existing routes (`/login`, `/admin/dashboard`, etc.) are unaffected.

---

## API Contract Reference

The contract is already defined in the requirements. No new API contract document is
needed — the contract lives in `requirements.md` under "API Contract".

When epic-classes implements `GET /api/schedule`, the backend developer must reference
that contract to ensure the response shape matches exactly.

**Contract location:** `docs/working_docs/epics/epic-public-site/requirements.md#api-contract`

---

## State Machine

No state machines. The only stateful logic is the schedule hook:

```
[mount] → loading → success → rendered
                  ↘ error   → rendered with mock data (isMock = true)
```

---

## Testing Strategy

| Test Type | Scope | Priority | Tool |
|-----------|-------|----------|------|
| Unit | `usePublicSchedule` — success case, error/fallback case | High | Vitest + React Testing Library |
| Component | `ScheduleSection` — renders with mock data | High | Vitest + RTL |
| Component | `PricingSection` — renders all 3 plans with correct prices | High | Vitest + RTL |
| Component | `ClassTypesSection` — renders all 5 class types | Medium | Vitest + RTL |
| Component | `PublicHeader` — login link points to `/login` | Medium | Vitest + RTL |
| Component | `ContactSection` — renders address, email, hours | Medium | Vitest + RTL |
| Router | Route `/` renders `PublicHomePage` without auth | High | Vitest + RTL + MemoryRouter |
| Manual smoke | Full page on mobile (320px) and desktop (1280px) | High | Browser |

**Test file location:** Co-located with components at `__tests__/` or `.test.tsx` suffix.

---

## Implementation Order

1. [ ] Types: `frontend/src/types/schedule.ts`
2. [ ] Static data: `frontend/src/data/publicSiteData.ts`
3. [ ] Hook: `frontend/src/hooks/usePublicSchedule.ts` + unit tests
4. [ ] Layout: `PublicLayout`, `PublicHeader`, `PublicMobileMenu`, `PublicFooter`
5. [ ] Section: `HeroSection`
6. [ ] Section: `AboutSection`
7. [ ] Section: `ClassTypesSection` + component test
8. [ ] Section: `ScheduleSection` + component test
9. [ ] Section: `PricingSection` + component test
10. [ ] Section: `ContactSection` + component test
11. [ ] Page: `PublicHomePage`
12. [ ] Router: add `/` route to `router/index.tsx` + router test
13. [ ] Manual smoke test (mobile + desktop)

---

## Dependencies

| Dependency | Type | Notes |
|------------|------|-------|
| React Router v6 | Already installed | Used for `Link` and route definition |
| Axios (`api.ts`) | Already installed | Used by `usePublicSchedule` |
| Tailwind CSS | Already installed | All styling |
| Vitest + React Testing Library | Already in project (assumed from epic-foundation) | Tests |
| `GET /api/schedule` backend endpoint | External — epic-classes | Not blocking — mock fallback covers MVP |

---

## Open Technical Questions

1. **Tailwind config:** Are custom brand colors (`#0f172a`, `#2563eb`, `#60a5fa`) in
   `tailwind.config.js` as named tokens, or used as inline arbitrary values like
   `bg-[#0f172a]`? Recommendation: use arbitrary values consistently (same pattern as
   `LoginPage.tsx`) — no config change needed.

2. **Google Maps CSP:** The Laravel backend may have a `Content-Security-Policy` header
   that blocks iframe embeds from `maps.google.com`. If Maps embed does not render, add
   `frame-src https://maps.google.com` to the CSP config in Laravel middleware. This is
   a deployment-time fix, not a code change.

3. **Smooth scroll:** CSS `scroll-behavior: smooth` should be added to the `html` element
   or via Tailwind `scroll-smooth` on the root layout. This enables native smooth
   scrolling for anchor links with no JS needed.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| `GET /api/schedule` not available when frontend ships | High | Low | Mock fallback is the designed solution — user sees correct data |
| Google Maps iframe CSP block | Low | Low | Add `frame-src` CSP exception — 5-minute fix |
| Mobile header overflow on very small screens (320px) | Low | Low | Test on 320px during implementation; use `overflow-hidden` on header if needed |
| `AuthProvider` fires `GET /auth/me` on public page load — unnecessary network call | Certain | Negligible | Expected behaviour; returns 401, sets `user = null`, no redirect (public route has no `ProtectedRoute` wrapper) |
