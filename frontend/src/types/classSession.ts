export interface ClassSession {
  id: string
  day_of_week: string
  time_slot: string
  class_type: { id: string; name: string; slug: string; color: string }
  coach: { id: string; email: string } | null
  max_capacity: number
  status: string
}

export interface CreateClassSessionPayload {
  class_type_id: string
  coach_user_id: string
  day_of_week: string
  time_slot: string
  max_capacity: number
}
