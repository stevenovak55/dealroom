# MA Deal Room - React Admin Interface

Modern React SPA for managing real estate transactions in Massachusetts.

## Tech Stack

- **React 18+** with TypeScript
- **Vite** - Fast build tooling
- **Tailwind CSS** - Utility-first styling
- **React Query (TanStack Query)** - API state management
- **Zustand** - Local state management
- **React Hook Form + Zod** - Form validation
- **React Router** - Client-side routing
- **Recharts** - Timeline visualization
- **Lucide React** - Icon library

## Project Structure

```
src/
├── api/                  # API client and React Query hooks
│   ├── client.ts        # Axios instance with WordPress nonce
│   ├── types.ts         # TypeScript types for API responses
│   └── queries/         # React Query hooks
│       ├── useTransactions.ts
│       ├── useTasks.ts
│       ├── useTemplates.ts
│       └── useReminders.ts
├── components/          # React components
│   ├── Layout/         # App shell, sidebar, header
│   ├── Tasks/          # Task card, list, filters
│   ├── Timeline/       # Timeline visualization
│   └── shared/         # Reusable UI components
├── pages/              # Page components
│   ├── Dashboard/      # Dashboard with widgets
│   ├── Transactions/   # Transaction list, detail, wizard
│   ├── Templates/      # Template list
│   ├── Reminders/      # Reminder list
│   └── Settings/       # Settings page
├── store/              # Zustand stores
│   ├── useAuthStore.ts
│   ├── useUIStore.ts
│   └── useFilterStore.ts
├── routes/             # React Router configuration
├── utils/              # Helper functions
└── styles/             # Global styles

```

## Getting Started

### Prerequisites

- Node.js 18+ and npm
- WordPress site with MA Deal Room plugin installed

### Installation

```bash
cd ma-deal-room/assets/admin
npm install
```

### Development

Start the development server:

```bash
npm run dev
```

This will start Vite dev server on `http://localhost:3000`.

### Building for Production

Build the production bundle:

```bash
npm run build
```

This generates optimized files in the `dist/` directory that WordPress will load.

### Linting and Formatting

```bash
# Run ESLint
npm run lint

# Format with Prettier
npm run format
```

## WordPress Integration

The React app integrates with WordPress via:

1. **Root Element**: Mounts to `#ma-deal-room-app` div
2. **WordPress Data**: Accessed via `window.maDealRoom` object:
   - `apiUrl` - REST API base URL
   - `nonce` - WordPress REST API nonce
   - `currentUser` - Current user ID

3. **REST API**: All data fetched from `/wp-json/ma-deal/v1/` endpoints

## Key Features

### Transaction Management
- Create transactions with multi-step wizard
- View transaction details with tabs (property, tasks, parties, activity)
- Filter and search transactions
- Track key dates (P&S, Closing)

### Task Management
- View tasks grouped by status
- Complete or skip tasks
- Filter by status, assignee, overdue
- Task dependencies support

### Timeline Visualization
- Gantt-style chart showing all tasks
- Color-coded by status (pending, completed, overdue)
- Milestones visualization

### Templates
- Browse available task templates
- Preview template tasks before using
- Automatic task generation on transaction creation

### Reminders
- View upcoming reminders
- Email/SMS delivery tracking
- Failure reason display

### Settings
- Account preferences
- Notification settings (email, SMS, daily digest)
- Branding customization

## API Endpoints Used

- `GET /transactions` - List transactions
- `GET /transactions/:id` - Get transaction
- `POST /transactions` - Create transaction
- `PUT /transactions/:id` - Update transaction
- `DELETE /transactions/:id` - Delete transaction
- `GET /tasks` - List tasks
- `GET /tasks/:id` - Get task
- `POST /tasks/:id/complete` - Complete task
- `POST /tasks/:id/skip` - Skip task
- `GET /templates` - List templates
- `GET /templates/:id` - Get template
- `GET /reminders/upcoming` - Get upcoming reminders
- `GET /transactions/:id/parties` - Get transaction parties
- `POST /transactions/:id/parties` - Add party
- `GET /transactions/:id/events` - Get transaction activity

## Component Architecture

### Shared Components
- `Button` - Styled button with variants (primary, secondary, danger, ghost)
- `Badge` - Status badges with color variants
- `Card` - Container component with header/content/footer
- `Input` - Form input with validation
- `Select` - Dropdown select
- `Modal` - Modal dialog
- `Table` - Data table with sorting
- `Loader` - Loading spinner
- `EmptyState` - Empty state with illustration

### State Management
- **useAuthStore** - User authentication state
- **useUIStore** - UI preferences (sidebar collapsed)
- **useFilterStore** - List filters (transactions, tasks)

### React Query
All API calls use React Query for:
- Automatic caching
- Background refetching
- Optimistic updates
- Loading and error states

## Development Notes

### Adding New Pages
1. Create page component in `src/pages/`
2. Add route in `src/routes/AppRoutes.tsx`
3. Add navigation link in `src/components/Layout/Sidebar.tsx`

### Adding New API Hooks
1. Create query hook in `src/api/queries/`
2. Define TypeScript types in `src/api/types.ts`
3. Use hook in component with `useQuery` or `useMutation`

### Styling Guidelines
- Use Tailwind utility classes
- Follow color palette:
  - Primary: Blue (#3b82f6)
  - Success: Green (#22c55e)
  - Warning: Yellow (#f59e0b)
  - Danger: Red (#ef4444)
- Mobile-first responsive design

## Troubleshooting

### React app not loading
- Check browser console for errors
- Verify `window.maDealRoom` is defined
- Check that WordPress REST API is accessible

### API calls failing
- Verify WordPress nonce is valid
- Check user permissions
- Inspect network tab for error responses

### Build errors
- Run `npm install` to ensure dependencies are up-to-date
- Clear `dist/` directory and rebuild
- Check for TypeScript errors with `npm run build`

## Contributing

When adding features:
1. Follow existing code patterns
2. Add TypeScript types for new data structures
3. Write reusable components
4. Use React Query for API calls
5. Add proper error handling and loading states

## License

Part of MA Deal Room WordPress plugin.
