# epic-public-site — Public Website

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-10
**Author:** Requirement Writer Agent
**Phase:** 2 — Core Modules (parallel with epic-members and epic-classes)

---

## Business Alignment

**Objective:** Member Acquisition / Digital Presence
**KPI Target:** Every potential new member can find complete information about the gym (schedule, pricing, contact) without needing to call or visit in person.
**Evidence:** The gym currently has no branded public web presence. New members discover the gym through Instagram (@itsvallhallaworkout) or word of mouth, then must contact the gym manually to get schedule or pricing information. This creates friction in the acquisition funnel.

**Business Value:**
- Reduces inbound calls for info that could be self-served
- Provides a professional first digital impression aligned with the brand
- Acts as the entry point that links to the member login flow

---

## Problem Statement

### Current Situation

Valhalla Gym has no public website with complete information. Potential members who want to know the class schedule, membership prices, or gym location must:
1. Find the gym via Instagram (@itsvallhallaworkout)
2. Send a DM or call to ask basic questions (prices, schedule, address)
3. Decide whether to join without a clear, structured information source

The existing platform (if any) has no management features and no branded public page.

### Pain Points

- Prospective members cannot self-serve basic information (schedule, pricing, location)
- No digital first impression aligned with the Valhalla brand identity
- Staff time wasted answering repetitive questions via WhatsApp/phone
- The class schedule lives only in internal tools — not publicly visible
- No route `/` exists in the current frontend — the app immediately redirects to `/login`

### Impact if Not Solved

- Missed member acquisitions due to friction in the information discovery phase
- Continued reliance on Instagram DMs and phone calls for basic info
- The MVP platform is incomplete: members can log in but prospects cannot learn about the gym

---

## Proposed Solution

A fully public, responsive React web page accessible at `/` (root route) requiring no authentication. The page presents the gym brand, class types, weekly schedule (pulled from the backend), membership pricing, and contact information. A "Login" link allows existing members to access their area.

The page is structured as a single-page with anchor-linked sections (no separate routes needed for MVP). It is mobile-first and uses the Valhalla brand colors.

---

### User Stories

#### US-001: Visitor views the homepage and discovers the gym

**As a** prospective gym member (visitor)
**I want** to land on a branded homepage that presents Valhalla Gym
**So that** I can form a first impression and understand what the gym offers

**Acceptance Criteria:**
- [ ] The root route `/` renders the public site without requiring authentication
- [ ] The page displays the gym name "VALHALLA GYM" and tagline "Donde los guerreros se forjan"
- [ ] There is a prominent CTA button visible above the fold ("Unete" or "Ver horario")
- [ ] The page uses the brand colors: dark bg #0f172a, primary blue #2563eb, accent #60a5fa
- [ ] The page renders correctly on mobile (320px+), tablet (768px+), and desktop (1280px+)
- [ ] A navigation header is visible with links to each section and a "Iniciar sesion" button linking to `/login`

---

#### US-002: Visitor reads about the gym and its philosophy

**As a** prospective member
**I want** to read about Valhalla Gym, its philosophy, and its specialization
**So that** I can decide if the gym's approach matches what I'm looking for

**Acceptance Criteria:**
- [ ] There is an "Sobre nosotros" section visible on the page
- [ ] The section describes the gym's focus on calisthenics and functional strength training
- [ ] The section mentions the gym's location (Los Palacios y Villafranca, Sevilla)
- [ ] The text does not contain the "n with tilde" character

---

#### US-003: Visitor views the types of classes offered

**As a** prospective member
**I want** to see what types of classes Valhalla Gym offers
**So that** I can understand if the training modality suits me

**Acceptance Criteria:**
- [ ] There is a "Tipos de clase" (or similar) section with one card per class type
- [ ] The following 5 class types are displayed: Tren Superior, Tren Inferior, Full Body, GAP, Entrenamiento Libre
- [ ] Each card shows the class name and a short description of the class type
- [ ] Cards are visually distinct and readable on mobile

---

#### US-004: Visitor sees the weekly class schedule

**As a** prospective or current member
**I want** to see the weekly class schedule (days and time slots)
**So that** I can plan when I would attend

**Acceptance Criteria:**
- [ ] There is a "Horario" section displaying a visual weekly schedule
- [ ] The schedule shows Monday through Friday with all 7 daily time slots: 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15
- [ ] Each cell in the schedule shows the class type for that day
- [ ] The schedule data is fetched from `GET /api/schedule` (public endpoint, no auth)
- [ ] If the backend endpoint is unavailable or returns an error, the schedule section shows a loading state and then a graceful fallback (static mock data with identical structure)
- [ ] The schedule is readable on mobile (horizontal scroll or stacked layout acceptable)

---

#### US-005: Visitor views membership plans and pricing

**As a** prospective member
**I want** to see the membership plan options and their prices
**So that** I can evaluate which plan fits my budget and training frequency

**Acceptance Criteria:**
- [ ] There is a "Planes" or "Membresias" section with one card per plan
- [ ] The following 3 plans are displayed:
  - Plan 2 dias: €35/mes, 8 clases, 2 dias/semana, vestuarios y duchas
  - Plan 3 dias: €38/mes, 12 clases, 3 dias/semana, vestuarios, duchas y asesoramiento
  - Plan 4-5 dias: €40/mes, 20-25 clases, acceso ilimitado, vestuarios, duchas y plan de entrenamiento
- [ ] Prices are clearly visible
- [ ] Each plan lists its included benefits
- [ ] The 4-5 day plan is visually highlighted as the most complete option
- [ ] No "Buy" or "Subscribe" button exists — this is informational only (cash payment at gym)

---

#### US-006: Visitor finds contact information and gym location

**As a** prospective member
**I want** to find the gym's address, phone, email, and opening hours
**So that** I can visit or contact the gym to sign up

**Acceptance Criteria:**
- [ ] There is a "Contacto" section at the bottom of the page
- [ ] The section displays:
  - Address: C. Agustina de Aragon, 26 — 41720 Los Palacios y Villafranca, Sevilla
  - Email: info@valhallagym.com
  - Phone: +34 91 234 5678
  - Opening hours (Mon-Fri 06:00-23:00, Sat 08:00-22:00, Sun 08:00-20:00)
- [ ] A Google Maps embed is present showing the real gym address
- [ ] There is NO contact form that sends emails (info only, MVP constraint)
- [ ] Instagram link to @itsvallhallaworkout is present

---

#### US-007: Visitor navigates between sections

**As a** visitor
**I want** to navigate quickly between the different sections of the page
**So that** I can jump directly to the information I care about

**Acceptance Criteria:**
- [ ] The navigation header contains anchor links to each section (Sobre nosotros, Clases, Horario, Planes, Contacto)
- [ ] Clicking a nav link smoothly scrolls to the corresponding section
- [ ] The header is sticky (remains visible while scrolling) on desktop
- [ ] A "Iniciar sesion" button in the header links to `/login`

---

#### US-008: Existing member accesses their login from the public site

**As a** registered gym member landing on the public site
**I want** to find the login link easily
**So that** I can access my member area without searching

**Acceptance Criteria:**
- [ ] The header contains a visible "Iniciar sesion" button/link
- [ ] Clicking it navigates to `/login` (the existing login page)
- [ ] No redirect to `/login` happens automatically — the public site is accessible without authentication

---

## Entities

This epic introduces **no new backend entities**. It is a read-only public view of existing data.

| Entity Used | Source | Access |
|-------------|--------|--------|
| ClassSession / Schedule | Backend `GET /api/schedule` | Public (no auth) |
| ClassType | Embedded in schedule response | Read-only |
| MembershipPlan | Static data (hardcoded in frontend, MVP) | Read-only |

**Note on MembershipPlan:** Plan data (prices, benefits) is static and known. In MVP it is hardcoded in the frontend. A future epic can expose it via API if plans become configurable.

**Note on Schedule endpoint:** `GET /api/schedule` returns the weekly class schedule. If this endpoint does not exist at time of frontend implementation, a mock with identical JSON shape is used and replaced when the endpoint is available.

---

## Use Cases

### UC-001: Public visitor loads the homepage

**Actor:** Unauthenticated visitor (Guest)
**Preconditions:** User navigates to `https://valhallagym.com/`
**Postconditions:** Full public page rendered, no authentication required

**Main Flow:**
1. Browser requests `/`
2. React router matches the `/` route to `PublicHomePage`
3. `PublicHomePage` renders all sections in sequence
4. `ScheduleSection` triggers `usePublicSchedule` hook → calls `GET /api/schedule`
5. Schedule data renders in the schedule table
6. Page is fully interactive

**Alternative Flows:**
- A1: User is already authenticated (has token in localStorage)
  - The public page still renders normally — no redirect occurs
  - The header "Iniciar sesion" button is still visible (MVP: no conditional header)

**Error Scenarios:**
- E1: `GET /api/schedule` returns 5xx or network error
  - Schedule section shows a loading spinner, then falls back to static mock data
  - User sees schedule data regardless
- E2: `GET /api/schedule` is slow (>3s)
  - A skeleton loader or spinner is shown while data loads

---

### UC-002: Visitor clicks "Iniciar sesion"

**Actor:** Guest or registered member
**Preconditions:** Visitor is on the public page
**Postconditions:** User is navigated to `/login`

**Main Flow:**
1. Visitor clicks the "Iniciar sesion" button in the header (or any CTA linking to login)
2. React Router navigates to `/login`
3. Existing `LoginPage` renders

---

### UC-003: Visitor reads the class schedule

**Actor:** Guest or any user
**Preconditions:** Public page is loaded
**Postconditions:** Visitor sees the complete Mon-Fri schedule

**Main Flow:**
1. `ScheduleSection` mounts
2. `usePublicSchedule` hook fires `GET /api/schedule`
3. Response contains schedule data (days × time slots)
4. Schedule table renders with class type per cell

**Alternative Flows:**
- A1: Endpoint not yet implemented (during development)
  - `usePublicSchedule` uses mock data with the same shape
  - Functionally identical to the user

---

## API Contract

### GET /api/schedule (Public — no auth required)

**Purpose:** Returns the weekly class schedule for display on the public site.

**Request:**
```
GET /api/schedule
Authorization: none
```

**Expected Response (200 OK):**
```json
{
  "schedule": [
    {
      "day": "monday",
      "day_label": "Lunes",
      "class_type": {
        "slug": "tren-superior",
        "name": "Calistenia — Tren Superior"
      },
      "slots": ["07:45", "12:15", "16:15", "17:30", "18:45", "20:00", "21:15"]
    },
    {
      "day": "tuesday",
      "day_label": "Martes",
      "class_type": {
        "slug": "tren-inferior",
        "name": "Calistenia — Tren Inferior"
      },
      "slots": ["07:45", "12:15", "16:15", "17:30", "18:45", "20:00", "21:15"]
    },
    {
      "day": "wednesday",
      "day_label": "Miercoles",
      "class_type": {
        "slug": "tren-superior",
        "name": "Calistenia — Tren Superior"
      },
      "slots": ["07:45", "12:15", "16:15", "17:30", "18:45", "20:00", "21:15"]
    },
    {
      "day": "thursday",
      "day_label": "Jueves",
      "class_type": {
        "slug": "full-body",
        "name": "Calistenia — Full Body"
      },
      "slots": ["07:45", "12:15", "16:15", "17:30", "18:45", "20:00", "21:15"]
    },
    {
      "day": "friday",
      "day_label": "Viernes",
      "class_type": {
        "slug": "gap",
        "name": "GAP + Entrenamiento Libre"
      },
      "slots": ["07:45", "12:15", "16:15", "17:30", "18:45", "20:00", "21:15"]
    }
  ]
}
```

**Mock fallback:** Frontend uses this exact shape as static data when the endpoint is unavailable.

---

## Collateral Impact

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| `router/index.tsx` | Behavioral | Route `/` currently redirects to `/login` via `DefaultRedirect` | Add explicit `/` route for `PublicHomePage` before the catch-all |
| `DefaultRedirect` component | Behavioral | The catch-all `path="*"` redirects unauthenticated users to `/login`. Adding `/` as an explicit route removes the redirect for the homepage | No change needed to `DefaultRedirect` — just add the explicit route |
| `AuthContext` | None | Public page does not use `useAuth()`. No impact. | None |
| `api.ts` interceptor | Behavioral | The 401 interceptor redirects to `/login`. `GET /api/schedule` is public (no auth) so it will never return 401. | No change needed |
| `LoginPage` | None | No changes to login page | None |
| Backend schedule endpoint | New dependency | `GET /api/schedule` must exist or frontend uses mock | Backend team to expose public endpoint in epic-classes. Frontend uses mock until then. |

### Migration Requirements

- No data migrations required (new UI feature, no DB changes)
- No existing records affected

### Risk Assessment

| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| Route `/` conflicts with `DefaultRedirect` | High | Medium | Add explicit route before catch-all — standard React Router precedence handles this |
| `GET /api/schedule` not ready at implementation time | High | Low | Use static mock data — same JSON shape — replace with real call when endpoint ships |
| Google Maps embed blocked by CSP | Low | Low | Configure CSP in Laravel to allow Google Maps iframe domain |
| SEO not optimized (SPA) | Low | Low | Not a concern for MVP — gym is local, SEO is secondary |

---

## Out of Scope (MVP)

| Item | Reason | Info Needed Now |
|------|--------|----------------|
| Contact form that sends emails | No email service in MVP | None |
| Weekend schedule | TBD with gym owner | None |
| Animations / transitions beyond Tailwind | Complexity vs value | None |
| SEO optimization (meta tags, sitemap) | MVP scope | None |
| Dark/light mode toggle | Not in brand spec | None |
| Multi-language | Spanish only | None |
| Blog or news section | Future | None |
| Member self-registration form | Members registered by admin | None |

---

## Definition of Done

- [ ] Route `/` renders `PublicHomePage` without authentication
- [ ] All 6 sections render correctly: Hero, Sobre Nosotros, Tipos de Clase, Horario, Planes, Contacto
- [ ] `usePublicSchedule` hook calls `GET /api/schedule` (or falls back to mock on error)
- [ ] All 3 membership plans display with correct prices and benefits
- [ ] All 5 class types display with names and descriptions
- [ ] Schedule table shows Mon-Fri x 7 time slots with correct class types
- [ ] Google Maps embed shows gym address
- [ ] Header has "Iniciar sesion" link pointing to `/login`
- [ ] Header navigation links scroll to corresponding sections
- [ ] Page is responsive: works on 320px, 768px, 1280px
- [ ] No "n with tilde" character in any visible UI text
- [ ] Brand colors used consistently: #0f172a bg, #2563eb primary, #60a5fa accent
- [ ] Component tests pass for all section components
- [ ] `usePublicSchedule` hook has unit tests (success and error/fallback cases)
- [ ] `router/index.tsx` updated: `/` route added without breaking existing routes
- [ ] Code review approved
- [ ] Manual smoke test on mobile and desktop

---

## Time Constraints

**Deadline:** None (no hard business date)
**Type:** None
**Reason:** No external deadline. Priority is correctness and brand quality.
**Notes:** Can be parallelized with epic-members and epic-classes after epic-foundation is complete.

---

## Open Questions

1. **Phone number:** The business overview lists `+34 91 234 5678` as a placeholder. Confirm the real gym phone number before launch.
2. **Hero background:** No image/video asset is specified. Use a CSS gradient from brand colors for MVP, or confirm if a specific photo should be sourced from the gym's Instagram.
3. **"Unete" CTA destination:** Should the Hero CTA link to the `/login` page (for existing members) or scroll to the pricing section? Recommended: scroll to `#planes` for anonymous visitors.
4. **Google Maps URL:** Confirm the exact Google Maps embed URL for "C. Agustina de Aragon, 26, 41720 Los Palacios y Villafranca, Sevilla".
