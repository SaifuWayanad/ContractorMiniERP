import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type UserDetail = {
  id: number;
  name: string;
  username: string;
  email: string | null;
  phone: string | null;
  status: string;
  role: string | null;
  contractor: string | null;
};

export default function UserDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [user, setUser] = useState<UserDetail | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const res = await api.get(`/mobile/admin/users/${id}`);
      setUser(res.data);
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  if (!user) return <View style={s.centered}><Text style={{ color: Colors.textMuted }}>User not found.</Text></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title={user.name}
        subtitle={user.username}
        right={
          <TouchableOpacity
            style={s.editBtn}
            onPress={() => router.push({ pathname: '/(tabs)/admin/users/form', params: { id: String(user.id) } })}
          >
            <Ionicons name="create-outline" size={20} color={Colors.primary} />
          </TouchableOpacity>
        }
      />
      <ScrollView contentContainerStyle={s.content} showsVerticalScrollIndicator={false}>
        <Card title="Account Info">
          <View style={s.row}><Text style={s.label}>Full Name</Text><Text style={s.value}>{user.name}</Text></View>
          <View style={s.row}><Text style={s.label}>Username</Text><Text style={s.value}>{user.username}</Text></View>
          {user.email && <View style={s.row}><Text style={s.label}>Email</Text><Text style={s.value}>{user.email}</Text></View>}
          {user.phone && <View style={s.row}><Text style={s.label}>Phone</Text><Text style={s.value}>{user.phone}</Text></View>}
          {user.role && <View style={s.row}><Text style={s.label}>Role</Text><Text style={s.value}>{user.role}</Text></View>}
          {user.contractor && <View style={s.row}><Text style={s.label}>Contractor</Text><Text style={s.value}>{user.contractor}</Text></View>}
          <View style={[s.row, { borderBottomWidth: 0 }]}>
            <Text style={s.label}>Status</Text>
            <Badge status={user.status} />
          </View>
        </Card>
      </ScrollView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  editBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.accent,
    alignItems: 'center', justifyContent: 'center',
  },
  row: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  label: { fontSize: 13, color: Colors.textSecondary },
  value: { fontSize: 13, fontWeight: '600', color: Colors.text },
});
