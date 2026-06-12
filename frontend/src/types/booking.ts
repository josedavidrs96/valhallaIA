export interface Booking {
  id: string
  member_id: string
  class_session_id: string
  session_date: string
  status: string
  session: {
    day_of_week: string
    time_slot: string
    class_type_name: string
    class_type_slug: string
  }
  created_at: string | null
}

export interface BookingsListResponse {
  data: Booking[]
  weekly_used: number
  weekly_max: number
}

export interface RosterItem {
  booking_id: string
  member_id: string
  member_number: number
  first_name: string
  last_name: string
  status: string
  booked_at: string | null
}

export interface RosterResponse {
  capacity: { confirmed: number; available: number; max: number }
  roster: RosterItem[]
}
