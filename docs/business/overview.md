# Business Overview — Valhalla Gym

## Description

Valhalla Gym is a local gym located in Los Palacios y Villafranca (Sevilla, Spain), specializing
in calisthenics and functional strength training. The tagline is "Donde los guerreros se forjan".
The gym has a strong community identity and operates class-based training with defined daily slots.

This project replaces existing manual/third-party processes (paper, WhatsApp, external booking app)
with a branded web application owned by the gym.

## Business Model

Single-location gym. One installation, one gym. Not SaaS.

## Contact Information

| Field      | Value                                                           |
|------------|-----------------------------------------------------------------|
| Address    | C. Agustina de Aragon, 26 — 41720 Los Palacios y Villafranca, Sevilla, Espana |
| Phone      | +34 91 234 5678                                                 |
| Email      | info@valhallagym.com                                            |
| Instagram  | @itsvallhallaworkout                                            |

## Operating Hours

| Day              | Open       | Close      |
|------------------|------------|------------|
| Monday–Friday    | 06:00 AM   | 11:00 PM   |
| Saturday         | 08:00 AM   | 10:00 PM   |
| Sunday           | 08:00 AM   | 08:00 PM   |

## Class Schedule (Monday to Friday)

Daily time slots: **07:45 · 12:15 · 16:15 · 17:30 · 18:45 · 20:00 · 21:15**

Each day has a fixed class type. Every time slot on a given day runs the same class type.
Classes have a coach assigned and a limited number of spots. Members must book in advance.

| Day       | Class Type                              |
|-----------|-----------------------------------------|
| Monday    | Calistenia — Tren Superior (upper body) |
| Tuesday   | Calistenia — Tren Inferior (lower body) |
| Wednesday | Calistenia — Tren Superior (upper body) |
| Thursday  | Calistenia — Full Body                  |
| Friday    | GAP + Entrenamiento Libre               |

### Class Types Catalogue

| ID (slug)           | Display Name (ES)               | Category      |
|---------------------|---------------------------------|---------------|
| tren-superior       | Calistenia — Tren Superior      | Calisthenics  |
| tren-inferior       | Calistenia — Tren Inferior      | Calisthenics  |
| full-body           | Calistenia — Full Body          | Calisthenics  |
| gap                 | GAP                             | Conditioning  |
| entrenamiento-libre | Entrenamiento Libre             | Free Training |

> Weekend class schedule TBD — to be defined with the gym owner.

## Membership Plans (2026)

| Plan     | Price       | Classes/month | Gym Access              | Extras                              |
|----------|-------------|---------------|-------------------------|-------------------------------------|
| 2 Days   | €35 / month | 8 classes     | 2 days/week             | Changing rooms and showers          |
| 3 Days   | €38 / month | 12 classes    | 3 days/week             | Changing rooms, showers, advice     |
| 4-5 Days | €40 / month | 20-25 classes | Unlimited               | Changing rooms, showers, training plan |

Payment method: **cash only** (admin registers payments manually in the system).

## User Roles

| Role              | Access                                                                 |
|-------------------|------------------------------------------------------------------------|
| Admin / Owner     | Full access: members, classes, payments, staff, configuration          |
| Coach / Trainer   | View assigned classes, see attendee list, mark attendance              |
| Member / Socio    | View own membership, book classes, see payment history                 |
| Guest (public)    | View public website: info, class schedule, pricing, contact form       |

## Brand Identity

- **Primary color:** #2563eb (blue)
- **Dark background:** #0f172a (dark blue/black)
- **Accent:** #60a5fa (light blue)
- **Logo source:** Instagram @itsvallhallaworkout
- **UI language:** Spanish (avoiding the character "n with tilde" — use "n" instead)
- **Tagline:** "Donde los guerreros se forjan"

## Business Goals

**MVP goal:** Digitize and centralize gym management. Replace paper-based processes and the
external third-party booking app with a single owned platform.

### Problems Being Solved

1. Class booking currently handled by an external third-party app (not branded, no data ownership)
2. Member registration and payment tracking done manually (paper or spreadsheets)
3. No unified digital presence — basic static website with no management features

### Success Criteria (MVP)

- Admin can register members and assign them a membership plan
- Admin can record monthly payments and see who is overdue
- Admin can create and manage the weekly class schedule
- Members can log in and book a class slot
- Public visitors can see gym info, class schedule, and pricing without logging in

## Key Business Constraints

- Payment is always cash — no payment gateway integration needed in MVP
- The gym is a single location — no multi-tenancy
- Membership plans are predefined (not configurable per member in MVP)
- Class spots are limited — booking must respect capacity limits
