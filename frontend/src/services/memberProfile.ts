import api from './api'
import type { MemberProfile, MemberPayment } from '@/types/profile'

interface MemberPaymentsResponse {
  data: MemberPayment[]
}

export const memberProfileService = {
  getProfile: () => api.get<MemberProfile>('/member/profile'),
  getPayments: () => api.get<MemberPaymentsResponse>('/member/payments'),
  getSchedule: () => api.get<Record<string, unknown[]>>('/schedule'),
}
