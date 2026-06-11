import api from './api'
import type { MembershipPlan } from '@/types/plan'

export const plansService = {
  list: () => api.get<{ data: MembershipPlan[] }>('/admin/membership-plans'),
}
