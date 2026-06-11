import api from './api'
import type { ClassType, Coach } from '@/types/classType'

export const classTypesService = {
  list: () => api.get<{ data: ClassType[] }>('/admin/class-types'),
}

export const coachesService = {
  list: () => api.get<{ data: Coach[] }>('/admin/coaches'),
}
