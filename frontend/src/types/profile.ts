export interface MemberProfile {
  id: string
  user_id: string
  member_number: number
  first_name: string
  last_name: string
  email: string
  phone: string | null
  date_of_birth: string | null
  profile_photo: string | null
  join_date: string
  status: string
  emergency_contact_name: string | null
  emergency_contact_phone: string | null
  notes: string | null
  created_at: string | null
  plan: {
    id: string
    name: string
    price_cents: number
    classes_per_month: number
  } | null
}

export interface MemberPayment {
  id: string
  amount_cents: number
  payment_date: string
  billing_month: string
  plan_name: string
}
