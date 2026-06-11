import api from './api'
import type { Booking } from '@/types/booking'

interface BookingsListResponse {
  data: Booking[]
}

export const bookingsService = {
  // Admin
  getMemberBookings: (memberId: string) =>
    api.get<BookingsListResponse>(`/admin/members/${memberId}/bookings`),

  // Member
  myBookings: () =>
    api.get<BookingsListResponse>('/member/bookings'),

  create: (classSessionId: string) =>
    api.post<Booking>('/member/bookings', { class_session_id: classSessionId }),

  cancel: (bookingId: string) =>
    api.patch<Booking>(`/member/bookings/${bookingId}/cancel`),
}
