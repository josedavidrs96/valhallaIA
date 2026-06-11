<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Member;

use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

final class MemberTest extends TestCase
{
    private function makeMember(array $overrides = []): Member
    {
        return Member::create(
            id:           $overrides['id']           ?? MemberId::random(),
            userId:       $overrides['userId']       ?? UserId::random(),
            memberNumber: $overrides['memberNumber'] ?? 1,
            firstName:    $overrides['firstName']    ?? 'Carlos',
            lastName:     $overrides['lastName']     ?? 'Ruiz',
            joinDate:     $overrides['joinDate']     ?? new \DateTimeImmutable('2026-06-10'),
            phone:        $overrides['phone']        ?? null,
            dateOfBirth:  $overrides['dateOfBirth']  ?? null,
        );
    }

    public function test_create_assigns_required_fields(): void
    {
        $id     = MemberId::random();
        $userId = UserId::random();
        $join   = new \DateTimeImmutable('2026-06-10');

        $member = Member::create(
            id:           $id,
            userId:       $userId,
            memberNumber: 5,
            firstName:    'Ana',
            lastName:     'Lopez',
            joinDate:     $join,
        );

        $this->assertSame($id, $member->id);
        $this->assertSame($userId, $member->userId);
        $this->assertSame(5, $member->memberNumber);
        $this->assertSame('Ana', $member->firstName);
        $this->assertSame('Lopez', $member->lastName);
        $this->assertSame($join, $member->joinDate);
    }

    public function test_create_sets_nullable_fields_to_null(): void
    {
        $member = $this->makeMember();

        $this->assertNull($member->phone);
        $this->assertNull($member->dateOfBirth);
        $this->assertNull($member->profilePhoto);
        $this->assertNull($member->emergencyContactName);
        $this->assertNull($member->emergencyContactPhone);
        $this->assertNull($member->notes);
    }

    public function test_create_with_optional_fields(): void
    {
        $dob    = new \DateTimeImmutable('1990-05-15');
        $member = $this->makeMember(['phone' => '+34 600 000 000', 'dateOfBirth' => $dob]);

        $this->assertSame('+34 600 000 000', $member->phone);
        $this->assertSame($dob, $member->dateOfBirth);
    }

    public function test_update_returns_new_instance(): void
    {
        $original = $this->makeMember();
        $updated  = $original->update(
            firstName:            'Maria',
            lastName:             'Garcia',
            phone:                '+34 611 111 111',
            dateOfBirth:          null,
            emergencyContactName: 'Pedro Garcia',
            emergencyContactPhone: '+34 622 222 222',
            notes:                'Hipertension',
            profilePhoto:         null,
        );

        $this->assertNotSame($original, $updated);
        $this->assertSame('Maria', $updated->firstName);
        $this->assertSame('Garcia', $updated->lastName);
        $this->assertSame('+34 611 111 111', $updated->phone);
        $this->assertSame('Pedro Garcia', $updated->emergencyContactName);
    }

    public function test_update_does_not_mutate_original(): void
    {
        $original = $this->makeMember(['firstName' => 'Carlos']);
        $original->update(
            firstName:            'Maria',
            lastName:             'Garcia',
            phone:                null,
            dateOfBirth:          null,
            emergencyContactName: null,
            emergencyContactPhone: null,
            notes:                null,
            profilePhoto:         null,
        );

        // Original must remain unchanged
        $this->assertSame('Carlos', $original->firstName);
    }

    public function test_update_preserves_join_date(): void
    {
        $join     = new \DateTimeImmutable('2026-01-01');
        $original = $this->makeMember(['joinDate' => $join]);

        $updated = $original->update(
            firstName:            'Nueva',
            lastName:             'Persona',
            phone:                null,
            dateOfBirth:          null,
            emergencyContactName: null,
            emergencyContactPhone: null,
            notes:                null,
            profilePhoto:         null,
        );

        $this->assertSame($join, $updated->joinDate);
    }

    public function test_update_preserves_id_user_id_and_member_number(): void
    {
        $original = $this->makeMember(['memberNumber' => 7]);
        $updated  = $original->update('A', 'B', null, null, null, null, null, null);

        $this->assertSame($original->id, $updated->id);
        $this->assertSame($original->userId, $updated->userId);
        $this->assertSame(7, $updated->memberNumber);
    }
}
