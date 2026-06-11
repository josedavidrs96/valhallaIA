import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { usePublicSchedule } from '../usePublicSchedule'
import { MOCK_SCHEDULE } from '@/data/publicSiteData'

// Mock the api module
vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
  },
}))

import api from '@/services/api'

const mockedApiGet = vi.mocked(api.get)

const API_SCHEDULE_DATA = [
  {
    day: 'monday',
    day_label: 'Lunes',
    class_type: { slug: 'tren-superior', name: 'Tren Superior API' },
    slots: ['07:45', '12:15'],
  },
]

describe('usePublicSchedule', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.resetAllMocks()
  })

  it('returns loading state initially', () => {
    // Never resolves during this assertion
    mockedApiGet.mockReturnValue(new Promise(() => {}))

    const { result } = renderHook(() => usePublicSchedule())

    expect(result.current.isLoading).toBe(true)
    expect(result.current.schedule).toEqual([])
  })

  it('success path: returns API schedule data and isMock = false', async () => {
    mockedApiGet.mockResolvedValueOnce({
      data: { schedule: API_SCHEDULE_DATA },
    })

    const { result } = renderHook(() => usePublicSchedule())

    await waitFor(() => expect(result.current.isLoading).toBe(false))

    expect(result.current.schedule).toEqual(API_SCHEDULE_DATA)
    expect(result.current.isMock).toBe(false)
    expect(result.current.isError).toBe(false)
  })

  it('error fallback: returns MOCK_SCHEDULE and isMock = true when API rejects', async () => {
    mockedApiGet.mockRejectedValueOnce(new Error('Network Error'))

    const { result } = renderHook(() => usePublicSchedule())

    await waitFor(() => expect(result.current.isLoading).toBe(false))

    expect(result.current.schedule).toEqual(MOCK_SCHEDULE)
    expect(result.current.isMock).toBe(true)
    expect(result.current.isError).toBe(true)
  })
})
