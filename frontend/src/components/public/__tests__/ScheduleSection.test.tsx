import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ScheduleSection } from '../ScheduleSection'
import { MOCK_SCHEDULE } from '@/data/publicSiteData'
import type { ScheduleDay } from '@/types/schedule'

// Mock the hook
vi.mock('@/hooks/usePublicSchedule')
import { usePublicSchedule } from '@/hooks/usePublicSchedule'

const mockedUsePublicSchedule = vi.mocked(usePublicSchedule)

describe('ScheduleSection', () => {
  it('shows loading skeleton while isLoading = true', () => {
    mockedUsePublicSchedule.mockReturnValue({
      schedule: [],
      isLoading: true,
      isError: false,
      isMock: false,
    })

    render(<ScheduleSection />)

    // Table should not be present
    expect(screen.queryByRole('table')).toBeNull()
    // Loading aria label should be present
    expect(screen.getByLabelText('Cargando horario')).toBeDefined()
  })

  it('renders table with 5 day columns and 7 slot rows when data is loaded', () => {
    mockedUsePublicSchedule.mockReturnValue({
      schedule: MOCK_SCHEDULE,
      isLoading: false,
      isError: false,
      isMock: false,
    })

    render(<ScheduleSection />)

    const table = screen.getByRole('table')
    expect(table).toBeDefined()

    // 5 day column headers
    const dayHeaders = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']
    dayHeaders.forEach((day) => {
      expect(screen.getByText(day)).toBeDefined()
    })

    // 7 time slot rows (first slot of each day)
    const timeSlots = ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15']
    timeSlots.forEach((slot) => {
      // There may be multiple cells with the same time but at least one should exist
      expect(screen.getAllByText(slot).length).toBeGreaterThan(0)
    })
  })

  it('shows "Datos aproximados" badge when isMock = true', () => {
    mockedUsePublicSchedule.mockReturnValue({
      schedule: MOCK_SCHEDULE as ScheduleDay[],
      isLoading: false,
      isError: true,
      isMock: true,
    })

    render(<ScheduleSection />)

    expect(screen.getByText('Datos aproximados')).toBeDefined()
    // Table should still render
    expect(screen.getByRole('table')).toBeDefined()
  })
})
