import api from './api'
import type { Member, MemberListItem, CreateMemberPayload, UpdateMemberPayload, AssignPlanPayload } from '@/types/member'

interface ListMembersResponse {
  data: MemberListItem[]
  meta: { total: number; page: number; per_page: number }
}

export const membersService = {
  list: (params?: Record<string, unknown>) =>
    api.get<ListMembersResponse>('/admin/members', { params }),

  get: (id: string) =>
    api.get<Member>(`/admin/members/${id}`),

  create: (data: CreateMemberPayload) =>
    api.post<Member>('/admin/members', data),

  update: (id: string, data: UpdateMemberPayload) =>
    api.put<Member>(`/admin/members/${id}`, data),

  assignPlan: (id: string, data: AssignPlanPayload) =>
    api.put<Member>(`/admin/members/${id}/plan`, data),

  activate: (id: string) =>
    api.put<Member>(`/admin/members/${id}/activate`),

  deactivate: (id: string) =>
    api.put<Member>(`/admin/members/${id}/deactivate`),
}
