# Quick start

## Installation

Install Frigate with composer:

```bash
composer require siktec/frigate
```

## Create a project

You may want to start with a bootstrap project to get started:

```bash
composer create-project siktec/frigate-bootstrap
```

## Recommended Project Structure

```bash
├── root
│   │
│   ├── api # API endpoints goes here (PSR-4)
│   ├── routes # Routes goes here (PSR-4)
│   ├── models # Models goes here (PSR-4)
│   ├── static # Static files goes here
│   ├── pages # Front-end stuff goes here
│   ├── cli
│   │
│   ├── index.php
```