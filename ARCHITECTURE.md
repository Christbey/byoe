# Coffee Shop Marketplace - Clean Architecture

## 🎯 Dual-Platform Support

This application supports **BOTH** web and mobile platforms with shared backend logic.

```
┌─────────────────────────────────────────────────┐
│           Client Applications                    │
├──────────────────────┬──────────────────────────┤
│   Inertia Web App    │    Mobile App            │
│   (Vue 3 + Inertia)  │    (React Native/Flutter)│
│   Session Auth       │    Token Auth (Sanctum)  │
├──────────────────────┴──────────────────────────┤
│              Laravel Backend API                 │
├──────────────────────┬──────────────────────────┤
│   Web Controllers    │    API Controllers       │
│   /marketplace/*     │    /api/v1/*             │
│   Returns: Inertia   │    Returns: JSON         │
├──────────────────────┴──────────────────────────┤
│         Shared Business Logic Layer              │
│  Services, Actions, Jobs (NO duplication)        │
├─────────────────────────────────────────────────┤
│              Data Layer                          │
│  Models, Migrations, Factories                   │
└─────────────────────────────────────────────────┘
```

## 🔐 Dual Authentication System

### Web App (Inertia)
- **Auth Method**: Session-based (cookies)
- **Routes**: `/marketplace/*`, `/shop/*`, `/provider/*`, `/admin/*`
- **Response**: Inertia pages (Vue components)
- **Auth Guard**: `web` (default)

### Mobile App (Future)
- **Auth Method**: Token-based (Sanctum)
- **Routes**: `/api/v1/*`
- **Response**: JSON (API Resources)
- **Auth Guard**: `sanctum`

### Legacy API (Temporary)
- **Routes**: `/api/provider/*`, `/api/service-requests/*`
- **Purpose**: Support current Inertia app's API calls
- **Will be**: Deprecated once web controllers are fully refactored

## 📁 Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/                    # FUTURE - Refactored Inertia controllers
│   │   │   ├── Shop/
│   │   │   ├── Provider/
│   │   │   └── Admin/
│   │   │
│   │   ├── Api/                    # Mobile API controllers
│   │   │   └── V1/
│   │   │       ├── Auth/
│   │   │       │   ├── LoginController.php
│   │   │       │   ├── RegisterController.php
│   │   │       │   └── LogoutController.php
│   │   │       ├── Provider/
│   │   │       │   ├── DashboardController.php
│   │   │       │   ├── BookingController.php
│   │   │       │   └── AcceptRequestController.php
│   │   │       └── Shop/
│   │   │           ├── DashboardController.php
│   │   │           └── ServiceRequestController.php
│   │   │
│   │   ├── Shop/                   # CURRENT - Inertia controllers
│   │   ├── Provider/               # CURRENT - Inertia controllers
│   │   └── Admin/                  # CURRENT - Inertia controllers
│   │
│   └── Resources/
│       └── Api/
│           └── V1/
│               ├── BookingResource.php
│               ├── ProviderResource.php
│               ├── ServiceRequestResource.php
│               └── ShopResource.php
│
├── Services/                       # SHARED business logic
│   ├── BookingService.php
│   ├── ProviderService.php
│   └── ShopService.php
│
└── Actions/                        # SHARED single-responsibility actions
    ├── AcceptServiceRequestAction.php
    └── CreateServiceRequestAction.php
```

## 🚀 API Endpoints

### Mobile API (v1) - Token Auth

#### Authentication
```
POST   /api/v1/auth/register       Register new user
POST   /api/v1/auth/login          Get auth token
POST   /api/v1/auth/logout         Revoke token
GET    /api/v1/auth/user           Get current user
```

#### Provider
```
GET    /api/v1/provider/dashboard
GET    /api/v1/provider/profile
PUT    /api/v1/provider/profile
GET    /api/v1/provider/bookings
GET    /api/v1/provider/bookings/{id}
GET    /api/v1/provider/available-requests
POST   /api/v1/provider/requests/{id}/accept
```

#### Shop
```
GET    /api/v1/shop/dashboard
POST   /api/v1/shop/service-requests
GET    /api/v1/shop/bookings
POST   /api/v1/shop/bookings/{id}/complete
```

### Web Routes - Session Auth
```
All existing /marketplace, /shop, /provider, /admin routes
```

## 🔧 How It Works

### Example: Provider Dashboard

#### 1. **Shared Service** (Business Logic)
```php
// app/Services/ProviderService.php
class ProviderService
{
    public function getDashboardData(Provider $provider): array
    {
        return [
            'stats' => [
                'earningsThisMonth' => $this->calculateEarnings($provider),
                'upcomingBookings' => $this->getUpcomingBookings($provider),
                'completedBookings' => $provider->bookings()->completed()->count(),
                'averageRating' => round($provider->rating_average, 1),
            ],
            'upcomingBookings' => $provider->upcomingBookings()->limit(5)->get(),
            'recentActivity' => $provider->completedBookings()->limit(5)->get(),
        ];
    }
}
```

#### 2. **Web Controller** (Inertia - for browser)
```php
// app/Http/Controllers/Provider/DashboardController.php
class DashboardController extends Controller
{
    public function __invoke(Request $request, ProviderService $service): Response
    {
        $data = $service->getDashboardData($request->user()->provider);
        
        // Returns Inertia page for web browser
        return Inertia::render('provider/Dashboard', $data);
    }
}
```

#### 3. **API Controller** (JSON - for mobile)
```php
// app/Http/Controllers/Api/V1/Provider/DashboardController.php
class DashboardController extends Controller
{
    public function __invoke(Request $request, ProviderService $service): JsonResponse
    {
        $data = $service->getDashboardData($request->user()->provider);
        
        // Returns JSON for mobile app
        return response()->json($data);
    }
}
```

## ✅ Benefits

1. **No Code Duplication** - Business logic shared between web and mobile
2. **Clean Separation** - Web and API concerns are separate
3. **Easy Testing** - Test services independently
4. **API Versioning** - `/api/v1/` allows future changes
5. **Type Safety** - API Resources ensure consistent JSON
6. **Web App Works** - Current Inertia app unaffected
7. **Mobile Ready** - Token auth ready for mobile apps

## 📋 Migration Plan

### Phase 1: ✅ Foundation (DONE)
- [x] Install Sanctum
- [x] Configure dual authentication
- [x] Create API route structure
- [x] Update User model with HasApiTokens

### Phase 2: Create API Layer (NEXT)
- [ ] Create API Resources (JSON transformers)
- [ ] Create API Auth controllers (login, register)
- [ ] Create API Provider controllers
- [ ] Create API Shop controllers
- [ ] Test API endpoints

### Phase 3: Refactor Web Controllers (LATER)
- [ ] Move current controllers to `Web/` namespace
- [ ] Extract business logic to Services
- [ ] Update controller imports
- [ ] Test web app still works

### Phase 4: Documentation
- [ ] Generate API documentation
- [ ] Create Postman collection
- [ ] Write mobile integration guide

## 🧪 Testing Both Platforms

### Web App (Inertia)
```bash
# Visit in browser
https://byoe.test/provider/dashboard

# Uses session cookies automatically
```

### Mobile API
```bash
# 1. Login and get token
curl -X POST https://byoe.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Response: {"token": "abc123..."}

# 2. Use token in subsequent requests
curl -X GET https://byoe.test/api/v1/provider/dashboard \
  -H "Authorization: Bearer abc123..."
```

## 📱 Mobile App Integration

When building your React Native/Flutter app:

1. **Authentication Flow**
   - POST to `/api/v1/auth/login`
   - Store token securely (KeyChain/Keystore)
   - Include token in all requests

2. **Making Requests**
   ```javascript
   // React Native example
   const response = await fetch('https://byoe.test/api/v1/provider/dashboard', {
     headers: {
       'Authorization': `Bearer ${token}`,
       'Accept': 'application/json',
     }
   });
   ```

3. **Handling Responses**
   - All responses are JSON
   - Consistent error format
   - Paginated responses follow Laravel standard

## 🎯 Current Status

✅ **Web App**: Fully functional with Inertia  
✅ **Backend**: Clean architecture foundation ready  
✅ **Authentication**: Dual auth configured (session + token)  
⏳ **Mobile API**: Routes defined, controllers need implementation  
⏳ **Documentation**: Needs API docs generation  

**Next Step**: Do you want me to implement the API controllers now?
