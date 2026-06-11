# Implementation Tasks: epic-public-site — Public Website

**Requirement:** [requirements.md](requirements.md)
**Validation:** [validation.md](validation.md)
**Solution Design:** [design.md](design.md)
**Created:** 2026-06-11
**Total Tasks:** 22
**Estimated Total Complexity:** M (all frontend, no backend changes)

> This epic is 100% frontend. The standard backend phases (Domain, Infrastructure, Application, HTTP)
> do not apply. Tasks are organized into frontend-specific phases that follow the same
> dependency logic: foundation first, then layout, then sections, then integration, then tests.

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Phase 1 — Foundation (types + data) | 2 | S |
| Phase 2 — Data hook | 2 | S–M |
| Phase 3 — Layout chrome | 4 | S–M |
| Phase 4 — Section components | 6 | S–M |
| Phase 5 — Page composition + Router | 2 | S |
| Phase 6 — Tests | 5 | M |
| Phase 7 — Collateral + smoke test | 1 | S |

---

## Phase 1: Foundation — Types and Static Data

### TASK-001: Create TypeScript types for schedule API

**Phase:** Foundation
**Complexity:** S
**Dependencies:** None

**Description:**
Create the TypeScript interfaces that match the `GET /api/schedule` API contract exactly.
These types are shared by the hook, the section component, and the mock data.

**File:** `frontend/src/types/schedule.ts`

**Interfaces to define:**
- `ClassTypeInfo` — `{ slug: string; name: string }`
- `ScheduleDay` — `{ day: string; day_label: string; class_type: ClassTypeInfo; slots: string[] }`
- `ScheduleResponse` — `{ schedule: ScheduleDay[] }`

**Acceptance Criteria:**
- [ ] File `frontend/src/types/schedule.ts` created
- [ ] `ClassTypeInfo`, `ScheduleDay`, `ScheduleResponse` interfaces exported
- [ ] Types are `readonly`-safe (no mutations expected)
- [ ] TypeScript compiles without errors

---

### TASK-002: Create static site data constants

**Phase:** Foundation
**Complexity:** S
**Dependencies:** TASK-001

**Description:**
Create a single file with all static data used across the public site: mock schedule,
membership plans, class type descriptions, and gym contact info. This avoids magic strings
scattered across components and makes content updates a single-file change.

**File:** `frontend/src/data/publicSiteData.ts`

**Exports to create:**
- `MOCK_SCHEDULE: ScheduleDay[]` — 5 days × 7 slots, matches API contract shape exactly
- `MembershipPlan` interface + `MEMBERSHIP_PLANS: MembershipPlan[]` — 3 plans with prices, benefits, `highlighted` flag
- `ClassTypeDisplay` interface + `CLASS_TYPES: ClassTypeDisplay[]` — 5 class types with descriptions
- `GYM_CONTACT` object — address, email, phone (with TODO comment), instagram, mapsEmbedUrl, hours array

**Acceptance Criteria:**
- [ ] File `frontend/src/data/publicSiteData.ts` created
- [ ] `MOCK_SCHEDULE` contains exactly 5 entries (Mon–Fri) with the 7 time slots each
- [ ] `MEMBERSHIP_PLANS` contains exactly 3 plans: 2-dias (€35), 3-dias (€38), 4-5-dias (€40)
- [ ] `CLASS_TYPES` contains exactly 5 entries matching the catalogue from business overview
- [ ] `GYM_CONTACT.phone` has a `// TODO: confirm real phone number` comment
- [ ] No "n with tilde" character in any string value
- [ ] TypeScript compiles without errors

---

## Phase 2: Data Hook

### TASK-003: Create `usePublicSchedule` hook

**Phase:** Data hook
**Complexity:** M
**Dependencies:** TASK-001, TASK-002

**Description:**
Create a React hook that fetches `GET /api/schedule` using the existing `api.ts` axios
client. On success it returns the API data. On any error (network, 4xx, 5xx) it falls back
to `MOCK_SCHEDULE` and sets `isMock = true`. Exposes loading and error state for the
consuming component.

**File:** `frontend/src/hooks/usePublicSchedule.ts`

**Return type:**
```ts
interface UsePublicScheduleResult {
  schedule: ScheduleDay[]
  isLoading: boolean
  isError: boolean
  isMock: boolean
}
```

**Behaviour:**
1. Initialises with `isLoading: true`, `schedule: []`
2. On mount: calls `api.get('/schedule')`
3. Success: sets `schedule` from `response.data.schedule`, `isLoading: false`, `isError: false`, `isMock: false`
4. Error: sets `schedule` to `MOCK_SCHEDULE`, `isLoading: false`, `isError: true`, `isMock: true`

**Acceptance Criteria:**
- [ ] File `frontend/src/hooks/usePublicSchedule.ts` created
- [ ] Uses `useEffect` + `useState` (no external state library)
- [ ] Uses `api` from `@/services/api` — not a raw `fetch` call
- [ ] Returns `{ schedule, isLoading, isError, isMock }`
- [ ] On success: `schedule` matches `response.data.schedule`, `isMock` is `false`
- [ ] On error: `schedule` is `MOCK_SCHEDULE`, `isMock` is `true`
- [ ] No memory leak: cancels or ignores stale requests if component unmounts

---

### TASK-004: Unit tests for `usePublicSchedule`

**Phase:** Data hook
**Complexity:** M
**Dependencies:** TASK-003

**Description:**
Write unit tests for the `usePublicSchedule` hook covering the success path, the error
fallback path, and the loading state transition.

**File:** `frontend/src/hooks/__tests__/usePublicSchedule.test.ts`

**Test cases:**
1. **Success path** — mock `api.get` to return a valid schedule response → `schedule` equals API data, `isMock = false`, `isLoading = false`
2. **Error fallback** — mock `api.get` to reject → `schedule` equals `MOCK_SCHEDULE`, `isMock = true`, `isError = true`
3. **Loading state** — while request is pending, `isLoading = true`

**Acceptance Criteria:**
- [ ] All 3 test cases implemented
- [ ] `api.ts` axios instance is mocked (not a real HTTP call)
- [ ] Tests pass: `npm run test` (or `vitest run`)
- [ ] No test relies on real network or real backend

---

## Phase 3: Layout Chrome

### TASK-005: Create `PublicMobileMenu` component

**Phase:** Layout chrome
**Complexity:** S
**Dependencies:** None (pure UI, no data)

**Description:**
Create the mobile navigation menu component. It is controlled externally (`isOpen` prop)
and renders a full-width dropdown below the header with anchor nav links and the login button.
Closes when any link is clicked.

**File:** `frontend/src/components/public/PublicMobileMenu.tsx`

**Props:**
```ts
interface PublicMobileMenuProps {
  isOpen: boolean
  onClose: () => void
}
```

**Nav links (same as desktop header):** Sobre nosotros · Clases · Horario · Planes · Contacto
**Login link:** "Iniciar sesion" → `/login` (React Router `<Link>`)

**Acceptance Criteria:**
- [ ] File created
- [ ] Renders `null` when `isOpen = false`
- [ ] Renders nav links and login button when `isOpen = true`
- [ ] Each nav link calls `onClose()` when clicked
- [ ] "Iniciar sesion" link uses React Router `<Link to="/login">`
- [ ] No "n with tilde" in visible text
- [ ] Uses brand colors (`#0f172a` bg, `#2563eb` for login button)

---

### TASK-006: Create `PublicHeader` component

**Phase:** Layout chrome
**Complexity:** M
**Dependencies:** TASK-005

**Description:**
Create the sticky page header with desktop nav links, logo text, login button, and mobile
hamburger icon that toggles `PublicMobileMenu`. Sticky on scroll via Tailwind `sticky top-0 z-50`.

**File:** `frontend/src/components/public/PublicHeader.tsx`

**Internal state:** `useState<boolean>(false)` for mobile menu open/closed.

**Desktop (md+):** Logo text left | Nav links center | Login button right
**Mobile (<md):** Logo text left | Hamburger icon right → toggles `PublicMobileMenu`

**Nav links (anchor links):**
- `href="#sobre-nosotros"` → Sobre nosotros
- `href="#clases"` → Clases
- `href="#horario"` → Horario
- `href="#planes"` → Planes
- `href="#contacto"` → Contacto

**Acceptance Criteria:**
- [ ] File created
- [ ] Sticky positioning: `sticky top-0 z-50`
- [ ] Desktop nav visible at `md:flex`, hidden on mobile
- [ ] Hamburger icon visible on mobile, hidden at `md:hidden`
- [ ] Clicking hamburger opens `PublicMobileMenu`
- [ ] Login link: `<Link to="/login">` with blue button styling
- [ ] Logo text "VALHALLA GYM" in white, bold
- [ ] Does NOT import or use `useAuth()`
- [ ] No "n with tilde" in visible text

---

### TASK-007: Create `PublicFooter` component

**Phase:** Layout chrome
**Complexity:** S
**Dependencies:** TASK-002 (uses `GYM_CONTACT.instagram`)

**Description:**
Create a minimal page footer with copyright notice and Instagram link.

**File:** `frontend/src/components/public/PublicFooter.tsx`

**Content:**
```
© 2026 Valhalla Gym — Los Palacios y Villafranca    [@itsvallhallaworkout ↗]
```

**Acceptance Criteria:**
- [ ] File created
- [ ] Instagram link opens in a new tab (`target="_blank" rel="noopener noreferrer"`)
- [ ] Uses `GYM_CONTACT.instagram` and `GYM_CONTACT.instagramHandle` from `publicSiteData.ts`
- [ ] Dark background (`bg-slate-900` or `bg-[#0f172a]`), muted text (`text-slate-500`)
- [ ] No "n with tilde" in visible text

---

### TASK-008: Create `PublicLayout` component

**Phase:** Layout chrome
**Complexity:** S
**Dependencies:** TASK-006, TASK-007

**Description:**
Create the layout wrapper that composes `PublicHeader`, the page content slot (`children`),
and `PublicFooter`. Also adds `scroll-smooth` to enable smooth anchor scrolling.

**File:** `frontend/src/layouts/PublicLayout.tsx`

**Structure:**
```tsx
<div className="min-h-screen bg-[#0f172a] scroll-smooth">
  <PublicHeader />
  <main>{children}</main>
  <PublicFooter />
</div>
```

**Acceptance Criteria:**
- [ ] File created
- [ ] Accepts `children: React.ReactNode`
- [ ] Renders `PublicHeader` above main content
- [ ] Renders `PublicFooter` below main content
- [ ] Root div has `bg-[#0f172a]` (dark background)
- [ ] Does NOT use `useAuth()` or wrap any auth context
- [ ] TypeScript compiles without errors

---

## Phase 4: Section Components

### TASK-009: Create `HeroSection` component

**Phase:** Sections
**Complexity:** S
**Dependencies:** None (no data, pure markup)

**Description:**
Create the hero section: full-viewport-height dark gradient background, gym name, tagline,
and two CTA anchor links ("Unete" → `#planes`, "Ver horario" → `#horario`).

**File:** `frontend/src/components/public/HeroSection.tsx`

**Layout:**
- `id="inicio"`, `min-h-screen`
- Background: `bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800`
- Center-aligned content
- `<h1>` VALHALLA GYM — white, large, bold, tracking-wider
- `<p>` Donde los guerreros se forjan — `text-[#60a5fa]`
- Primary CTA: `<a href="#planes">Unete</a>` — filled blue button (`bg-[#2563eb]`)
- Secondary CTA: `<a href="#horario">Ver horario</a>` — ghost/outline style

**Acceptance Criteria:**
- [ ] File created with `id="inicio"`
- [ ] Min height is full viewport (`min-h-screen`)
- [ ] Gym name and tagline render with correct colors
- [ ] "Unete" link points to `#planes`
- [ ] "Ver horario" link points to `#horario`
- [ ] No "n with tilde" in visible text
- [ ] Responsive: content is readable on 320px and 1280px

---

### TASK-010: Create `AboutSection` component

**Phase:** Sections
**Complexity:** S
**Dependencies:** None

**Description:**
Create the "Sobre nosotros" / "Quienes somos" section. Two-column layout on desktop
(text left, decorative accent right), single column on mobile.

**File:** `frontend/src/components/public/AboutSection.tsx`

**Content:**
- `id="sobre-nosotros"`
- Title: "Quienes somos"
- Body: description of the gym's specialization in calisthenics, location (Los Palacios y Villafranca, Sevilla), training philosophy (progressive, community, consistency)
- Decorative right column: styled div with brand color accent (e.g. a bordered blue block with a subtle pattern)

**Acceptance Criteria:**
- [ ] File created with `id="sobre-nosotros"`
- [ ] Section title visible
- [ ] Gym location mentioned (Los Palacios y Villafranca, Sevilla)
- [ ] Calisthenics specialization mentioned
- [ ] Two-column on `md+`, single column on mobile (`md:grid-cols-2`)
- [ ] No "n with tilde" in visible text

---

### TASK-011: Create `ClassTypesSection` component

**Phase:** Sections
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Create the class types section, rendering one card per entry in `CLASS_TYPES` (5 items).
3-column grid on desktop, 2 on tablet, 1 on mobile.

**File:** `frontend/src/components/public/ClassTypesSection.tsx`

**Each card:**
- Class name (bold, white)
- Category badge (small pill: `text-[#60a5fa]` border)
- Description text (`text-slate-300`)
- Dark card background (`bg-slate-800`)

**Acceptance Criteria:**
- [ ] File created with `id="clases"`
- [ ] Renders exactly 5 cards (one per entry in `CLASS_TYPES`)
- [ ] Each card shows name, category, and description
- [ ] Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
- [ ] Data sourced from `CLASS_TYPES` constant — no hardcoded strings in JSX
- [ ] No "n with tilde" in visible text (data file already handles this)

---

### TASK-012: Create `ScheduleSection` component

**Phase:** Sections
**Complexity:** M
**Dependencies:** TASK-003 (hook)

**Description:**
Create the weekly schedule section. Uses `usePublicSchedule()` to fetch data and renders
a table with time slots as rows and weekdays as columns. Handles loading (spinner), loaded
(table), and error (mock data with subtle "Datos aproximados" note) states.

**File:** `frontend/src/components/public/ScheduleSection.tsx`

**Table structure:**
- Header row: empty cell + day labels (Lunes, Martes, Miercoles, Jueves, Viernes)
- Data rows: one row per time slot
- Each cell: class type name for that day column (same class type fills all time cells of a given day)
- Mobile: `overflow-x-auto` wrapper around table

**Table construction:**
```ts
const slots = schedule[0]?.slots ?? []
// rows: slots.map(slot => <tr>...)
// cols: schedule.map(day => <td>...</td>)
```

**Acceptance Criteria:**
- [ ] File created with `id="horario"`
- [ ] Uses `usePublicSchedule()` hook
- [ ] Shows spinner/skeleton while `isLoading = true`
- [ ] Renders table with 5 day columns and 7 time slot rows when data is loaded
- [ ] Shows subtle "Datos aproximados" badge when `isMock = true`
- [ ] Table is horizontally scrollable on mobile (`overflow-x-auto`)
- [ ] No "n with tilde" in visible text

---

### TASK-013: Create `PricingSection` component

**Phase:** Sections
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Create the membership pricing section with 3 plan cards sourced from `MEMBERSHIP_PLANS`.
The highlighted plan (`highlighted: true`) receives a blue ring border and "Mas popular" badge.
No purchase CTA — footer note says "Pago en efectivo en el gimnasio".

**File:** `frontend/src/components/public/PricingSection.tsx`

**Each card:**
- Plan name
- Price: large `€XX` + `/mes` suffix
- Access label (days per week or "Acceso ilimitado")
- Benefits list with checkmark icons
- Highlighted card: `ring-2 ring-[#2563eb]` + "Mas popular" badge above card

**Acceptance Criteria:**
- [ ] File created with `id="planes"`
- [ ] Renders exactly 3 plan cards
- [ ] Prices correctly shown: €35, €38, €40
- [ ] Highlighted card (4-5 dias) has visible "Mas popular" indicator
- [ ] Benefits lists match the data in `MEMBERSHIP_PLANS`
- [ ] No CTA button on any card
- [ ] Footnote "Pago en efectivo en el gimnasio" visible below cards
- [ ] Data sourced from `MEMBERSHIP_PLANS` constant
- [ ] No "n with tilde" in visible text
- [ ] Layout: 3 columns on `lg+`, stacked on mobile

---

### TASK-014: Create `ContactSection` component

**Phase:** Sections
**Complexity:** M
**Dependencies:** TASK-002

**Description:**
Create the contact section with gym details on the left and a Google Maps embed on the right.
All data sourced from `GYM_CONTACT`. No email form — info only.

**File:** `frontend/src/components/public/ContactSection.tsx`

**Left column:**
- Address (with location icon)
- Phone (`GYM_CONTACT.phone`) — renders with `// TODO` comment in code
- Email (with mail icon, `mailto:` link)
- Opening hours table (3 rows: Mon–Fri, Sat, Sun)
- Instagram link (opens new tab)

**Right column:**
- `<iframe>` with `src={GYM_CONTACT.mapsEmbedUrl}`
- `loading="lazy"`, `allowFullScreen`
- Fixed aspect ratio: `aspect-video` or `h-64 md:h-96`

**Acceptance Criteria:**
- [ ] File created with `id="contacto"`
- [ ] Address renders from `GYM_CONTACT.address`
- [ ] Email renders as `mailto:` link
- [ ] Phone renders from `GYM_CONTACT.phone`
- [ ] Opening hours table shows all 3 rows with correct times
- [ ] Instagram link opens in new tab
- [ ] Google Maps iframe renders from `GYM_CONTACT.mapsEmbedUrl`
- [ ] Two-column on `md+`, stacked on mobile
- [ ] No email form or submit button
- [ ] No "n with tilde" in visible text

---

## Phase 5: Page Composition and Router

### TASK-015: Create `PublicHomePage`

**Phase:** Page composition
**Complexity:** S
**Dependencies:** TASK-008, TASK-009, TASK-010, TASK-011, TASK-012, TASK-013, TASK-014

**Description:**
Create the root public page component. It is a pure composition of `PublicLayout` and all
6 section components. No state, no hooks, no logic at this level.

**File:** `frontend/src/pages/public/PublicHomePage.tsx`

```tsx
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

**Acceptance Criteria:**
- [ ] File created
- [ ] Renders all 6 sections in order: Hero, About, ClassTypes, Schedule, Pricing, Contact
- [ ] Uses `PublicLayout` as wrapper
- [ ] No state or hooks at this level
- [ ] TypeScript compiles without errors

---

### TASK-016: Update router — add public route `/`

**Phase:** Router
**Complexity:** S
**Dependencies:** TASK-015

**Description:**
Add the `/` route to `AppRouter` pointing to `PublicHomePage`. The route must be added
before the catch-all `path="*"` so React Router v6 matches it first. The `DefaultRedirect`
component and all existing routes remain unchanged.

**File:** `frontend/src/router/index.tsx`

**Changes:**
1. Add lazy import: `const PublicHomePage = lazy(() => import('@/pages/public/PublicHomePage'))`
2. Add route inside `<Routes>` before `<Route path="*" .../>`:
   ```tsx
   <Route path="/" element={<PublicHomePage />} />
   ```

**Acceptance Criteria:**
- [ ] `router/index.tsx` updated
- [ ] `PublicHomePage` lazy-imported using the same pattern as other pages
- [ ] Route `path="/"` added **before** the `path="*"` catch-all
- [ ] All existing routes (`/login`, `/admin/dashboard`, etc.) still work
- [ ] Navigating to `/` renders the public home page without auth
- [ ] TypeScript compiles without errors

---

## Phase 6: Tests

### TASK-017: Component test — `ScheduleSection`

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-012, TASK-004

**Description:**
Write component tests for `ScheduleSection`. Mock the `usePublicSchedule` hook to control
returned data. Test the loading state, the successful render with data, and the mock/error
fallback indicator.

**File:** `frontend/src/components/public/__tests__/ScheduleSection.test.tsx`

**Test cases:**
1. **Loading state** — hook returns `isLoading: true` → spinner or skeleton rendered, table absent
2. **Loaded with data** — hook returns 5 days × 7 slots → table with 5 columns and 7 rows
3. **Error/mock fallback** — hook returns `isMock: true` → "Datos aproximados" indicator visible, table still rendered

**Acceptance Criteria:**
- [ ] All 3 test cases implemented
- [ ] `usePublicSchedule` is mocked (not the real hook)
- [ ] Table structure assertions: 5 day headers, 7 slot rows
- [ ] Tests pass: `npm run test`

---

### TASK-018: Component test — `PricingSection`

**Phase:** Tests
**Complexity:** S
**Dependencies:** TASK-013

**Description:**
Write component tests for `PricingSection` verifying all 3 plans render with correct prices
and the highlighted plan has its badge.

**File:** `frontend/src/components/public/__tests__/PricingSection.test.tsx`

**Test cases:**
1. **Three plans rendered** — DOM contains 3 plan cards
2. **Prices correct** — "€35", "€38", "€40" visible in the document
3. **Highlighted plan** — "Mas popular" text visible for the 4-5 dias plan
4. **No purchase CTA** — no `<button>` elements inside plan cards
5. **Cash footnote** — "Pago en efectivo" text visible

**Acceptance Criteria:**
- [ ] All 5 test cases implemented
- [ ] Tests pass: `npm run test`

---

### TASK-019: Component test — `PublicHeader`

**Phase:** Tests
**Complexity:** S
**Dependencies:** TASK-006

**Description:**
Write component tests for `PublicHeader` verifying the login link target and nav links.
Render inside a `MemoryRouter` to support `<Link>`.

**File:** `frontend/src/components/public/__tests__/PublicHeader.test.tsx`

**Test cases:**
1. **Login link** — "Iniciar sesion" element has `href="/login"` (or renders a `<a>` pointing to `/login`)
2. **Nav links present** — all 5 section nav links render (Sobre nosotros, Clases, Horario, Planes, Contacto)
3. **Mobile menu toggle** — clicking hamburger icon toggles mobile menu visibility

**Acceptance Criteria:**
- [ ] All 3 test cases implemented
- [ ] Rendered inside `MemoryRouter`
- [ ] Tests pass: `npm run test`

---

### TASK-020: Router test — `/` accessible without auth

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-016

**Description:**
Write a route-level test verifying that navigating to `/` renders the public home page
without requiring authentication. Uses `MemoryRouter` initialised at `/`. Verifies that
`PublicHomePage` content is shown and no redirect to `/login` occurs for unauthenticated state.

**File:** `frontend/src/router/__tests__/publicRoute.test.tsx`

**Test cases:**
1. **Route `/` renders public content** — `MemoryRouter` at `/`, AuthContext mocked with `user: null` → "VALHALLA GYM" heading visible, no redirect to `/login`
2. **Catch-all still redirects** — `MemoryRouter` at `/non-existent`, no auth → redirects to `/login`

**Acceptance Criteria:**
- [ ] Both test cases implemented
- [ ] AuthContext is mocked to simulate unauthenticated user
- [ ] Test 1: public content renders at `/` with no auth
- [ ] Test 2: catch-all behaviour preserved for unknown paths
- [ ] Tests pass: `npm run test`

---

### TASK-021: Component test — `ContactSection`

**Phase:** Tests
**Complexity:** S
**Dependencies:** TASK-014

**Description:**
Write component tests for `ContactSection` verifying key contact information renders.

**File:** `frontend/src/components/public/__tests__/ContactSection.test.tsx`

**Test cases:**
1. **Address visible** — "Agustina de Aragon" text present in document
2. **Email link** — `mailto:info@valhallagym.com` link present
3. **Instagram link** — link to instagram URL opens in new tab (`target="_blank"`)
4. **Opening hours** — "06:00" and "23:00" visible (Mon-Fri hours)

**Acceptance Criteria:**
- [ ] All 4 test cases implemented
- [ ] Tests pass: `npm run test`

---

## Phase 7: Collateral and Smoke

### TASK-022: Manual smoke test — mobile and desktop

**Phase:** Collateral / QA
**Complexity:** S
**Dependencies:** All previous tasks

**Description:**
Perform manual end-to-end smoke test of the complete public site in a running dev
environment. Test on both mobile viewport (320px) and desktop (1280px).

**Checklist to verify:**

**Route and auth:**
- [ ] Navigating to `/` shows public home page (no redirect to `/login`)
- [ ] "Iniciar sesion" button navigates to `/login`
- [ ] Browser back from `/login` returns to `/`
- [ ] Navigating to `/non-existent` still redirects to `/login`

**Layout:**
- [ ] Header is sticky (stays at top while scrolling)
- [ ] On mobile (320px): hamburger icon visible, desktop nav hidden
- [ ] On desktop (1280px): full nav visible, hamburger hidden
- [ ] Mobile menu opens and closes correctly
- [ ] Clicking a nav link in mobile menu closes the menu and scrolls to section

**Sections:**
- [ ] Hero: gym name, tagline, both CTA buttons visible and functional
- [ ] About: title and gym description visible
- [ ] Class types: 5 cards visible with names and descriptions
- [ ] Schedule: table renders with 5 day columns and 7 time slot rows
- [ ] Pricing: 3 plan cards with correct prices, "Mas popular" badge on 4-5 dias
- [ ] Contact: address, email, phone, hours, Instagram link visible; Maps iframe loads

**Brand:**
- [ ] Dark background (`#0f172a`) throughout
- [ ] Blue accent (`#2563eb`, `#60a5fa`) used consistently
- [ ] No "n with tilde" character visible anywhere

**Responsive:**
- [ ] Schedule table scrolls horizontally on mobile
- [ ] All section layouts stack correctly on mobile

---

## Final Checklist

- [ ] All 22 tasks completed
- [ ] All automated tests passing (`npm run test`)
- [ ] TypeScript compiles without errors (`npm run build`)
- [ ] No "n with tilde" character in any visible UI text
- [ ] Manual smoke test completed on mobile (320px) and desktop (1280px)
- [ ] `router/index.tsx` updated — `/` route added without breaking existing routes
- [ ] Code reviewed
- [ ] `GYM_CONTACT.phone` marked with TODO comment for real number confirmation
