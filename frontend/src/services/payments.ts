import api from './api'
import type { Payment, PaymentListItem, OverdueMember, RecordPaymentPayload } from '@/types/payment'

interface ListPaymentsResponse {
  data: PaymentListItem[]
  meta: { total: number; page: number; per_page: number }
}

interface OverdueResponse {
  data: OverdueMember[]
  meta: { total: number }
}

export const paymentsService = {
  list: (params?: Record<string, unknown>) =>
    api.get<ListPaymentsResponse>('/admin/payments', { params }),

  get: (id: string) =>
    api.get<Payment>(`/admin/payments/${id}`),

  record: (data: RecordPaymentPayload) =>
    api.post<Payment>('/admin/payments', data),

  overdue: () =>
    api.get<OverdueResponse>('/admin/payments/overdue'),
}
