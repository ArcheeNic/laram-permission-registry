# Система виджетов Permission Registry

## Обзор

Виджеты — это plug-in механика для внедрения произвольных UI-блоков (кнопок, секций, плашек) в заранее определённые места интерфейса пакета. Виджеты живут в host-приложении, а пакет только предоставляет точки вставки («слоты») и реестр.

Под капотом каждый виджет — это Livewire-компонент, который рендерится в слоте по условию.

## Основные компоненты

### 1. Контракты
- `ArcheeNic\PermissionRegistry\Widgets\WidgetInterface` — контракт виджета
- `ArcheeNic\PermissionRegistry\Widgets\AbstractWidget` — базовая реализация с дефолтами

### 2. Реестр
- `ArcheeNic\PermissionRegistry\Widgets\WidgetRegistry` — singleton-хранилище зарегистрированных виджетов. Резолвит виджеты по имени слота и контексту.

### 3. Blade-компонент слота
- `<x-pr::widget-slot name="..." :context="[...]" />` — точка вставки. Опрашивает `WidgetRegistry`, рендерит подходящие виджеты как Livewire-компоненты.

### 4. Конфиг
- `config/permission-registry.php` → ключ `widgets` (массив FQCN для автоматической регистрации).

## Контракт виджета

```php
interface WidgetInterface
{
    // Имя слота, в который встраивается виджет.
    public function slot(): string;

    // Решение, показывать ли виджет в данном контексте.
    public function shouldRender(array $context): bool;

    // Имя Livewire-компонента (как при @livewire('...')).
    public function component(): string;

    // Пропсы, которые будут переданы Livewire-компоненту.
    public function props(array $context): array;
}
```

`AbstractWidget` даёт дефолты: `shouldRender()` возвращает `true`, `props()` пробрасывает весь `$context`. Наследуйтесь от него и переопределяйте только нужное.

## Встроенные слоты

| Слот | Где рендерится | Что лежит в `$context` |
|------|----------------|-----------------------|
| `user.card.actions` | Карточка пользователя (`user-edit-modal`), рядом с кнопками действий | `user` → `VirtualUser` |

Слот добавляется в шаблон так:

```blade
<x-pr::widget-slot
    name="user.card.actions"
    :context="['user' => $this->selectedUser]"
/>
```

Новые слоты можно добавлять тем же механизмом — достаточно воткнуть `<x-pr::widget-slot>` в любой нужной точке Blade-шаблонов пакета, договорившись об имени и форме контекста.

## Создание виджета

### 1. Класс-дескриптор

```php
namespace App\Widgets;

use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Widgets\AbstractWidget;

class ResetRegruPasswordWidget extends AbstractWidget
{
    public function slot(): string
    {
        return 'user.card.actions';
    }

    public function component(): string
    {
        return 'widgets.reset-regru-password-widget';
    }

    public function shouldRender(array $context): bool
    {
        $user = $context['user'] ?? null;
        if (! $user instanceof VirtualUser) {
            return false;
        }

        $domain = config('services.regru.email_domain');
        $email  = $user->email_for_display;

        return is_string($domain) && $domain !== ''
            && is_string($email)  && str_ends_with(mb_strtolower($email), '@' . mb_strtolower($domain));
    }

    public function props(array $context): array
    {
        $user = $context['user'];

        return [
            'virtualUserId' => $user->id,
            'email'         => $user->email_for_display,
        ];
    }
}
```

### 2. Livewire-компонент и view

Класс: `App\Livewire\Widgets\ResetRegruPasswordWidget` (автоматически получает имя `widgets.reset-regru-password-widget`).
View: `resources/views/livewire/widgets/reset-regru-password-widget.blade.php`.

Пропсы, объявленные в `public` свойствах Livewire-компонента, соответствуют ключам массива `props()` виджета.

### 3. Регистрация

Два способа — оба эквивалентны.

**A. Через ServiceProvider host-приложения:**

```php
use ArcheeNic\PermissionRegistry\Widgets\WidgetRegistry;
use App\Widgets\ResetRegruPasswordWidget;

public function boot(): void
{
    $this->app->make(WidgetRegistry::class)
        ->register(ResetRegruPasswordWidget::class);
}
```

**B. Через конфиг пакета** (`config/permission-registry.php`):

```php
return [
    // ...
    'widgets' => [
        \App\Widgets\ResetRegruPasswordWidget::class,
    ],
];
```

Пакет сам пройдётся по массиву в `ServiceProvider::registerConfiguredWidgets()` и позовёт `register()`.

## Жизненный цикл рендера

1. Blade встречает `<x-pr::widget-slot name="X" :context="[...]" />`.
2. Компонент тянет `WidgetRegistry` из контейнера и зовёт `forSlot('X', $context)`.
3. Реестр отфильтровывает виджеты: `slot() === 'X'` и `shouldRender($context) === true`.
4. Для каждого прошедшего виджета — `@livewire($widget->component(), $widget->props($context))`.
5. Дальше Livewire управляет состоянием и событиями компонента как обычно.

## Рекомендации

- **Тонкие виджет-классы.** Логика контракта — только: где рендерить, когда показывать, какие пропсы прокинуть. Всё состояние и действия — в Livewire-компоненте.
- **Быстрый `shouldRender()`.** Вызывается на каждом рендере слота. Не делайте тяжёлых запросов — максимум проверки свойств уже загруженной модели и конфига.
- **Пропсы — скаляры / id.** Livewire сериализует пропсы. Не передавайте целые модели, передавайте id и подгружайте внутри `mount()`.
- **Один виджет — один слот.** Если нужен один и тот же виджет в двух местах — сделайте два класса-дескриптора, либо два разных Livewire-компонента.
- **Именование.** Livewire 3 выводит имя компонента из пути: `App\Livewire\Foo\Bar` → `foo.bar`. Держите это в голове, задавая `component()`.

## Как добавить новый слот в пакет

1. Решите имя слота (dot-notation, по смыслу: `user.card.actions`, `user.list.row-actions`, `dashboard.tiles`).
2. Определитесь с контрактом контекста (какие ключи и типы приходят).
3. Вставьте `<x-pr::widget-slot name="..." :context="[...]" />` в нужный Blade-шаблон пакета.
4. Зафиксируйте слот и формат контекста в этой документации (таблица «Встроенные слоты»).
