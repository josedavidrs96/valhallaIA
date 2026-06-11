export interface Member {
  id: string
  user_id: string
  member_number: number
  first_name: string
  last_name: string
  email: string
  phone: string | null
  date_of_birth: string | null
  join_date: string
  status: string
  plan: {
    id: string
    name: string
    price_cents: number
    classes_per_month: number | null
  } | null
  created_at: string | null
}

export interface MemberListItem {
  id: string
  member_number: number
  first_name: string
  last_name: string
  email: string
  status: string
  plan: { id: string; name: string } | null
  join_date: string
}

export interface CreateMemberPayload {
  first_name: string
  last_name: string
  email: string
  phone?: string
  date_of_birth?: string
  join_date: string
  membership_plan_id?: string
}

export interface UpdateMemberPayload {
  first_name: string
  last_name: string
  phone?: string
  date_of_birth?: string
}

export interface AssignPlanPayload {
  membership_plan_id: string
}
