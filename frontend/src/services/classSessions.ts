import api from './api'
import type { ClassSession, CreateClassSessionPayload } from '@/types/classSession'
import type { RosterResponse } from '@/types/booking'

interface ListSessionsResponse {
  data: ClassSession[]
}

export const classSessionsService = {
  list: (params?: Record<string, unknown>) =>
    api.get<ListSessionsResponse>('/class-sessions', { params }),

  weeklySchedule: () =>
    api.get('/schedule'),

  get: (id: string) =>
    api.get<ClassSession>(`/class-sessions/${id}`),

  create: (data: CreateClassSessionPayload) => {
    // Backend uses coach_id, not coach_user_id
    const payload: Record<string, unknown> = {
      class_type_id: data.class_type_id,
      day_of_week:   data.day_of_week,
      time_slot:     data.time_slot,
      max_capacity:  data.max_capacity,
    }
    if (data.coach_user_id) payload.coach_id = data.coach_user_id
    return api.post<ClassSession>('/class-sessions', payload)
  },

  update: (id: string, data: Partial<CreateClassSessionPayload>) =>
    api.put<ClassSession>(`/class-sessions/${id}`, data),

  cancel: (id: string) =>
    api.patch<ClassSession>(`/class-sessions/${id}/cancel`),

  restore: (id: string) =>
    api.patch<ClassSession>(`/class-sessions/${id}/restore`),

  delete: (id: string) =>
    api.delete(`/class-sessions/${id}`),

  getRoster: (id: string) =>
    api.get<RosterResponse>(`/admin/class-sessions/${id}/roster`),
}
