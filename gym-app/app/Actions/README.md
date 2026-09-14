# app/Actions

App\Actions — single-purpose, invokable classes that each perform exactly one business operation (e.g. JoinTrainingAction, CreateTrainingAction). Called from Livewire components or console commands. An Action never calls another Action directly; if two operations must run together, that composition belongs in a Service.
