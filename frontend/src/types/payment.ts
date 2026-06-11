export interface Payment {
  id: string
  member: { id: string; member_number: number; name: string }
  plan: { id: string; name: string }
  recorded_by: string
  amount_cents: number
  payment_date: string
  billing_month: string
  notes: string | null
  created_at: string | null
}

export interface PaymentListItem {
  id: string
  member_number: number
  member_name: string
  plan_name: string
  amount_cents: number
  payment_date: string
  billing_month: string
}

export interface OverdueMember {
  member_id: string
  member_number: number
  first_name: string
  last_name: string
  email: string
  plan_name: string | null
  last_payment_date: string | null
}

export interface RecordPaymentPayload {
  member_id: string
  membership_plan_id: string
  amount_cents: number
  payment_date: string
  notes?: string
}
