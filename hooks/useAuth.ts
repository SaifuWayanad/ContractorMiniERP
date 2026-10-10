import { createContext, useContext } from 'react';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  contractor_id: number | null;
  is_admin: boolean;
};

export type AuthContextType = {
  user: AuthUser | null;
  token: string | null;
  signIn: (username: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  updateUser: (updated: AuthUser) => Promise<void>;
  isLoading: boolean;
};

export const AuthContext = createContext<AuthContextType>({
  user: null,
  token: null,
  signIn: async () => {},
  signOut: async () => {},
  updateUser: async () => {},
  isLoading: true,
});

export const useAuth = () => useContext(AuthContext);
