export interface ClassTypeInfo {
  slug: string
  name: string
}

export interface ScheduleDay {
  day: string
  day_label: string
  class_type: ClassTypeInfo
  slots: string[]
}

export interface ScheduleResponse {
  schedule: ScheduleDay[]
}
