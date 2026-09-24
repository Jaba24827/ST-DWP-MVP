<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /** The permission catalogue. Add a key here and it appears in the matrix. */
    public const PERMISSIONS = [
        ['dashboard.view',            'Open the dashboard',                      'work'],
        ['directory.view',            'Search the staff directory',              'work'],
        ['announcements.view',        'Read notices',                            'work'],
        ['announcements.publish',     'Publish and withdraw notices',            'work'],
        ['documents.view',            'Open public and internal documents',      'documents'],
        ['documents.view.restricted', 'Open restricted documents',               'documents'],
        ['documents.upload',          'Upload documents',                        'documents'],
        ['documents.delete',          'Delete documents',                        'documents'],
        ['users.manage',              'Create and edit user accounts',           'administration'],
        ['roles.manage',              'Change role permissions',                 'administration'],
        ['audit.view',                'Read the audit log',                      'administration'],
        ['account.security',          'Manage your own password and MFA',        'account'],
    ];

    /** Least privilege expressed as data, not as scattered if-statements. */
    public const MATRIX = [
        'Administrator'  => ['*'],
        'Sector manager' => ['dashboard.view','directory.view','announcements.view','announcements.publish',
                             'documents.view','documents.upload','account.security'],
        'Staff'          => ['dashboard.view','directory.view','announcements.view','documents.view','account.security'],
        'Auditor'        => ['dashboard.view','directory.view','announcements.view','documents.view',
                             'audit.view','account.security'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as [$key, $description, $group]) {
            Permission::updateOrCreate(['key' => $key], compact('description', 'group'));
        }

        foreach (self::MATRIX as $name => $keys) {
            $role = Role::updateOrCreate(['name' => $name], ['is_system' => true]);
            $ids  = $keys === ['*']
                ? Permission::pluck('id')
                : Permission::whereIn('key', $keys)->pluck('id');
            $role->permissions()->sync($ids);
        }
    }
}
