import { useEffect, useState } from 'react'
import api from '@/services/api'
import { MOCK_SCHEDULE } from '@/data/publicSiteData'
import type { ScheduleDay } from '@/types/schedule'

interface UsePublicScheduleResult {
  schedule: ScheduleDay[]
  isLoading: boolean
  isError: boolean
  isMock: boolean
}

export function usePublicSchedule(): UsePublicScheduleResult {
  const [schedule, setSchedule] = useState<ScheduleDay[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [isError, setIsError]     = useState(false)
  const [isMock, setIsMock]       = useState(false)

  useEffect(() => {
    let cancelled = false

    api
      .get<{ schedule: ScheduleDay[] }>('/schedule')
      .then((res) => {
        if (cancelled) return
        setSchedule(res.data.schedule)
        setIsError(false)
        setIsMock(false)
      })
      .catch(() => {
        if (cancelled) return
        setSchedule(MOCK_SCHEDULE)
        setIsError(true)
        setIsMock(true)
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [])

  return { schedule, isLoading, isError, isMock }
}
