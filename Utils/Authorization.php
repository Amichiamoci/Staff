<?php
namespace Amichiamoci\Utils;

use Amichiamoci\Models\{Staff, User, Parrocchia};

final class Authorization
{
    public static function CanAccessCertificate(
        User $user, 
        ?Staff $staff, 
        Parrocchia $parrocchia,
    ): bool
    {
        return $user->Admin || (
            $staff !== null 
            && $user->IdStaff === $staff->Id 
            && $staff->IsEnrolled()
            && $staff->Parrocchia->Id === $parrocchia->Id
        );
    }

    public static function CanAccessIdentityDocument(User $user): bool
    {
        return $user->Admin;
    }
}