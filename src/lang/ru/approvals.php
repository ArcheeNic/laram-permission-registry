<?php

return [
    'granted_status' => [
        'awaiting_approval' => 'Ожидает одобрения',
        'pending' => 'В очереди',
        'granting' => 'Выдаётся',
        'granted' => 'Выдано',
        'revoking' => 'Отзывается',
        'revoked' => 'Отозвано',
        'failed' => 'Ошибка',
        'partially_granted' => 'Частично выдано',
        'partially_revoked' => 'Частично отозвано',
        'rejected' => 'Отклонено',
        'manual_pending' => 'Ожидает ручной выдачи',
        'declared' => 'Декларативный',
    ],
    'status' => [
        'pending' => 'Ожидает',
        'approved' => 'Одобрено',
        'rejected' => 'Отклонено',
        'expired' => 'Просрочено',
        'cancelled' => 'Отменено',
    ],
    'type' => [
        'single' => 'Один одобряющий',
        'all' => 'Все одобряющие',
        'n_of_m' => 'N из M',
    ],
    'decision' => [
        'approved' => 'Одобрено',
        'rejected' => 'Отклонено',
    ],
    'approver_type' => [
        'virtual_user' => 'Пользователь',
        'position' => 'Должность',
    ],
];
