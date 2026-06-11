export interface MembershipPlan {
  id: string
  name: string
  slug: string
  price_cents: number
  classes_per_month: number | null
}
