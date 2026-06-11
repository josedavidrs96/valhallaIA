import api from './api'
import type { CoachSession } from '@/types/coachSession'
import type { RosterResponse } from '@/types/booking'

interface CoachSessionsResponse {
  data: CoachSession[]
}

export const coachSessionsService = {
  getMySessions: () => api.get<CoachSessionsResponse>('/coach/sessions'),
  // Coach-specific roster endpoint (different from admin endpoint)
  getSessionRoster: (sessionId: string) =>
    api.get<RosterResponse>(`/coach/class-sessions/${sessionId}/roster`),
}
