<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCrudController extends AbstractCrudController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'User Management')
            ->setDefaultSort(['email' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $actions
                ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
                ->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE)
                ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
                ->setPermissions([
                    ]
                );
        }

        return $actions
            ->disable(Action::DELETE, Action::SAVE_AND_CONTINUE, Action::NEW);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $entityInstance
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof User
            && $this->getUser() instanceof User
            && $this->getUser()->getId() === $entityInstance->getId()
        ) {
            $token = new UsernamePasswordToken(
                $entityInstance,
                'main',
                $entityInstance->getRoles()
            );

            $this->container->get('security.token_storage')->setToken($token);
            $session = $this->container->get('request_stack')
                ->getCurrentRequest()
                ->getSession();
            $session->set('_security_main', serialize($token));
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser(); // Get currently logged-in user
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles()); // Check if SUPER_ADMIN
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles()); // Check if ADMIN

        $entity = $this->getContext()?->getEntity()?->getInstance();
        $isDisabled = $entity instanceof User && in_array('ROLE_SUPER_ADMIN', $entity->getRoles());

        if(!$isSuperAdmin){
            return [
                EmailField::new('email')->setDisabled(true), // SUPER_ADMIN can edit, others cannot
                TextField::new('username')->setDisabled(true), // SUPER_ADMIN can edit

                BooleanField::new('isActive')
                    ->setLabel('Active') // Both SUPER_ADMIN and ADMIN can activate/deactivate users
                    ->setPermission('ROLE_ADMIN'), // Minimum role: Admin
                BooleanField::new('isBanned')
                    ->setLabel('Banned')
                    ->setPermission('ROLE_ADMIN')
                    ->setFormTypeOption('disabled', $isDisabled),
                TextField::new('rolesDisplay', 'Roles')
                    ->formatValue(function ($value, $entity) {
                        return implode(', ', $entity->getRoles()); // Convert array to string
                    })
                    ->onlyOnIndex()
            ];
        } else {
            return [
                EmailField::new('email')->setDisabled(false), // SUPER_ADMIN can edit, others cannot
                TextField::new('username')->setDisabled(false), // SUPER_ADMIN can edit
                BooleanField::new('isActive')
                    ->setLabel('Active') // Both SUPER_ADMIN and ADMIN can activate/deactivate users
                    ->setPermission('ROLE_ADMIN'), // Minimum role: Admin
                BooleanField::new('isBanned')
                    ->setLabel('Banned')
                    ->setPermission('ROLE_ADMIN')
                    ->setFormTypeOption('disabled', $isDisabled),
                ChoiceField::new('roles')
                    ->allowMultipleChoices()
                    ->setChoices($this->getAvailableRoles())
                    ->setPermission('ROLE_SUPER_ADMIN') // ADMIN can promote only to EDITOR
            ];
        }
    }

    /**
     * @return array<string>
     */
    private function getAvailableRoles(): array
    {
        // Only Super Admin can assign the "ROLE_SUPER_ADMIN"
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return [
                'User' => 'ROLE_USER',
                'Editor' => 'ROLE_EDITOR',
                'Admin' => 'ROLE_ADMIN',
                'Super Admin' => 'ROLE_SUPER_ADMIN'
            ];
        }

        // Hide "Super Admin" role for everyone else
        return [
            'User' => 'ROLE_USER',
            'Editor' => 'ROLE_EDITOR',
        ];
    }
}
